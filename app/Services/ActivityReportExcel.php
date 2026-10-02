<?php

namespace App\Services;

use App\Models\ActivityReport;
use RuntimeException;
use ZipArchive;

/** Writes the fixed, formula-free tracker workbook as Office Open XML. */
class ActivityReportExcel
{
    private const XMLNS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    private const WIDTHS = [25, 28, 32, 42, 15, 25, 28, 22, 22, 90];

    public function render(ActivityReport $report): string
    {
        $path = tempnam(sys_get_temp_dir(), 'osa-report-');
        if ($path === false) {
            throw new RuntimeException('Cannot create the workbook temporary file.');
        }
        try {
            $zip = new ZipArchive;
            if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Cannot open the workbook archive.');
            }
            $parts = [
                '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>',
                '_rels/.rels' => '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
                'xl/workbook.xml' => '<?xml version="1.0"?><workbook xmlns="'.self::XMLNS.'" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="In-campus" sheetId="1" r:id="rId1"/><sheet name="Off-campus" sheetId="2" r:id="rId2"/></sheets><definedNames><definedName name="_xlnm.Print_Titles" localSheetId="0">\'In-campus\'!$1:$6</definedName><definedName name="_xlnm.Print_Titles" localSheetId="1">\'Off-campus\'!$1:$6</definedName></definedNames></workbook>',
                'xl/_rels/workbook.xml.rels' => '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
                'xl/styles.xml' => $this->styles(),
                'xl/worksheets/sheet1.xml' => $this->sheet($report, 'in_campus', 'In-campus'),
                'xl/worksheets/sheet2.xml' => $this->sheet($report, 'off_campus', 'Off-campus'),
            ];
            foreach ($parts as $name => $xml) {
                if (! $zip->addFromString($name, $xml)) {
                    throw new RuntimeException('Cannot write the workbook archive.');
                }
            }
            if (! $zip->close()) {
                throw new RuntimeException('Cannot save the workbook archive.');
            }

            return file_get_contents($path);
        } finally {
            unlink($path);
        }
    }

    private function sheet(ActivityReport $report, string $type, string $label): string
    {
        $filters = collect($report->filter_labels)->map(fn ($value, $key) => "$key: $value")->implode(' | ');
        $xml = '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="'.self::XMLNS.'"><sheetPr><pageSetUpPr fitToPage="1"/></sheetPr><sheetViews><sheetView workbookViewId="0"><pane ySplit="6" topLeftCell="A7" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><cols>';
        foreach (self::WIDTHS as $index => $width) {
            $column = $index + 1;
            $xml .= '<col min="'.$column.'" max="'.$column.'" width="'.$width.'" customWidth="1"/>';
        }
        $xml .= '</cols><sheetData>';
        foreach ([
            'OSA Activity Report — '.$label,
            'Report '.$report->id.' | Generated '.$report->created_at->format('Y-m-d H:i:s T').' by '.$report->creator_name,
            $filters,
            'Progress: Pending = not started; Ongoing = unfinished; Completed = finished; Cancelled; Needs confirmation = not yet classified.',
            'Not approved = denied request. Expected participants = initial submitted list. Person in charge = submitting officer. Moderator = assigned moderator(s).',
        ] as $index => $value) {
            $number = $index + 1;
            $xml .= '<row r="'.$number.'" ht="30" customHeight="1">'.$this->cell('A'.$number, $value, $index === 0 ? 1 : 0).'</row>';
        }
        $xml .= '<row r="6" ht="32" customHeight="1">';
        foreach (array_values(ActivityReportService::COLUMNS) as $index => $value) {
            $xml .= $this->cell(chr(65 + $index).'6', $value, 1);
        }
        $xml .= '</row>';
        $number = 6;
        foreach (collect($report->rows)->where('activity_type', $type) as $row) {
            $number++;
            $cells = '';
            $lines = 1;
            foreach (array_keys(ActivityReportService::COLUMNS) as $index => $key) {
                $value = $row[$key];
                $lines = max($lines, (int) ceil(mb_strlen((string) $value) / (self::WIDTHS[$index] - 3)) + substr_count((string) $value, "\n"));
                $style = match ($value) {
                    'Completed' => 2, 'Ongoing' => 3, 'Pending', 'Needs confirmation' => 4,
                    'Cancelled', 'Not approved' => 5, default => 0,
                };
                $cells .= $this->cell(chr(65 + $index).$number, $value, in_array($key, ['progress', 'approval']) ? $style : 0);
            }
            $xml .= '<row r="'.$number.'" ht="'.min(409, max(32, $lines * 16 + 8)).'" customHeight="1">'.$cells.'</row>';
        }
        if ($number === 6) {
            $xml .= '<row r="7">'.$this->cell('A7', 'No matching activities.', 0).'</row>';
        }
        $xml .= '</sheetData><autoFilter ref="A6:J'.max(6, $number).'"/><mergeCells count="5">';
        for ($i = 1; $i <= 5; $i++) {
            $xml .= '<mergeCell ref="A'.$i.':J'.$i.'"/>';
        }

        return $xml.'</mergeCells><pageMargins left="0.25" right="0.25" top="0.4" bottom="0.4" header="0.2" footer="0.2"/><pageSetup paperSize="8" orientation="landscape" fitToWidth="1" fitToHeight="0"/></worksheet>';
    }

    private function cell(string $reference, mixed $value, int $style): string
    {
        if (is_int($value)) {
            return '<c r="'.$reference.'" s="'.$style.'"><v>'.$value.'</v></c>';
        }
        // Literal inline strings cannot be interpreted as spreadsheet formulas.
        $text = htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $text);

        return '<c r="'.$reference.'" s="'.$style.'" t="inlineStr"><is><t xml:space="preserve">'.$text.'</t></is></c>';
    }

    private function styles(): string
    {
        $xml = '<?xml version="1.0"?><styleSheet xmlns="'.self::XMLNS.'"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts><fills count="7"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>';
        foreach (['0B1930', 'DCFCE7', 'DBEAFE', 'FEF3C7', 'FEE2E2'] as $color) {
            $xml .= '<fill><patternFill patternType="solid"><fgColor rgb="FF'.$color.'"/><bgColor indexed="64"/></patternFill></fill>';
        }
        $xml .= '</fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="6">';
        foreach ([0, 2, 3, 4, 5, 6] as $index => $fill) {
            $xml .= '<xf numFmtId="0" fontId="'.($index === 1 ? 1 : 0).'" fillId="'.$fill.'" borderId="0" xfId="0" applyAlignment="1" applyFill="1" applyFont="1"><alignment vertical="top" wrapText="1"/></xf>';
        }

        return $xml.'</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }
}
