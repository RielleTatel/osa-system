<?php

use App\Models\ActivityRequest;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

beforeEach(function () {
    Storage::fake('local');
});

it('preserves PDF records across pages and newline-heavy remarks', function () {
    $pdftotext = (new ExecutableFinder)->find('pdftotext');
    if (! $pdftotext) {
        $this->markTestSkipped('Install Poppler to run the PDF text regression.');
    }
    ActivityRequest::factory()->create([
        'title' => 'First report activity',
        'tracker_remarks' => str_repeat("A\n", 80).'END OF REMARKS',
    ]);
    ActivityRequest::factory()->count(30)->create(['title' => 'Middle report activity']);
    ActivityRequest::factory()->offCampus()->create(['title' => 'Last report activity']);
    $url = $this->actingAs(admin())->post('/tracker/reports')->assertRedirect()->headers->get('Location');
    $pdf = $this->get($url.'/download/pdf')->assertOk()->streamedContent();
    $process = new Process([$pdftotext, '-layout', '-', '-']);
    $process->setInput($pdf)->mustRun();
    $text = $process->getOutput();
    expect(preg_replace('/\s+/u', ' ', $text))->toContain('First report activity', 'END OF REMARKS', 'Last report activity')
        ->and(substr_count($text, 'Middle report activity'))->toBe(30)
        ->and(substr_count($text, 'Approval'))->toBeGreaterThan(2)
        ->and(substr_count($text, "\f"))->toBeGreaterThan(2);
});

it('downloads a genuine two-sheet Excel file and PDF from the same saved snapshot', function () {
    $activity = ActivityRequest::factory()->create(['title' => '=Literal title & <tag>', 'expected_participants' => 42]);
    ActivityRequest::factory()->offCampus()->create(['title' => 'Off-campus fieldwork']);
    $url = $this->actingAs(admin())->post('/tracker/reports')->assertRedirect()->headers->get('Location');
    $excel = $this->get($url.'/download/xlsx')->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')->streamedContent();
    $temporary = tempnam(sys_get_temp_dir(), 'tracker-test-');
    try {
        file_put_contents($temporary, $excel);
        $zip = new ZipArchive;
        expect($zip->open($temporary))->toBeTrue();
        expect($zip->getFromName('xl/workbook.xml'))->toContain('In-campus', 'Off-campus');
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        expect($sheet)->toContain('=Literal title &amp; &lt;tag&gt;', '<v>42</v>')->not->toContain('<f>');
        expect($zip->getFromName('xl/worksheets/sheet2.xml'))->toContain('Off-campus fieldwork');
        $zip->close();
    } finally {
        unlink($temporary);
    }
    $pdf = $this->get($url.'/download/pdf')->assertOk()->assertHeader('content-type', 'application/pdf')->streamedContent();
    expect($pdf)->toStartWith('%PDF-');
    $activity->update(['title' => 'Later correction']);
    expect($this->get($url.'/download/xlsx')->streamedContent())->toBe($excel);
    expect($this->get($url.'/download/pdf')->streamedContent())->toBe($pdf);
});

it('saves a report of all matching activities and retains the issued data', function () {
    $activity = ActivityRequest::factory()->create(['title' => 'Original activity', 'progress' => 'ongoing', 'expected_participants' => 42]);
    ActivityRequest::factory()->count(21)->create(['title' => 'Matching activity']);
    $response = $this->actingAs(director())->post('/tracker/reports')->assertRedirect();
    $url = $response->headers->get('Location');
    $this->get($url)->assertOk()->assertSee('Original activity')->assertSee('22 activities')->assertSee('42');
    $activity->update(['title' => 'Changed later', 'progress' => 'completed']);
    $this->get($url)->assertSee('Original activity')->assertDontSee('Changed later');
    $this->get('/tracker/reports')->assertOk()->assertSee($url, false);
});

it('restricts report creation, history and both download formats to OSA staff', function () {
    $url = $this->actingAs(admin())->post('/tracker/reports')->headers->get('Location');
    $activity = ActivityRequest::factory()->create();
    foreach ([officer(), moderatorFor($activity)] as $user) {
        $this->actingAs($user)->post('/tracker/reports')->assertForbidden();
        foreach (['/tracker/reports', $url, $url.'/download/xlsx', $url.'/download/pdf'] as $path) {
            $this->get($path)->assertForbidden();
        }
    }
    $this->actingAs(director())->get($url.'/download/pdf')->assertOk();
    $this->get($url.'/download/csv')->assertNotFound();
});

it('exports only filtered rows, including boundary dates, and handles empty reports', function () {
    ActivityRequest::factory()->create(['title' => 'Boundary activity', 'date_start' => '2026-09-28', 'date_end' => '2026-10-03']);
    ActivityRequest::factory()->create(['title' => 'Outside activity', 'date_start' => '2026-11-01', 'date_end' => '2026-11-02']);
    ActivityRequest::factory()->create(['title' => 'Unsubmitted draft', 'status' => 'draft']);
    $url = $this->actingAs(admin())->post('/tracker/reports', ['from' => '2026-10-03', 'to' => '2026-10-03'])->assertRedirect()->headers->get('Location');
    $this->get($url)->assertSee('Boundary activity')->assertDontSee('Outside activity')->assertDontSee('Unsubmitted draft')
        ->assertSee('1 activities')->assertSee('2026-10-03');
    $empty = $this->post('/tracker/reports', ['from' => '2030-01-01'])->headers->get('Location');
    $this->get($empty)->assertSee('0 activities')->assertSee('No matching activities.');
    $this->get($empty.'/download/xlsx')->assertOk();
    $this->get($empty.'/download/pdf')->assertOk();
});
