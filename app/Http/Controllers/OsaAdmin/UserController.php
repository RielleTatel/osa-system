<?php

namespace App\Http\Controllers\OsaAdmin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('organization')->orderBy('name')->paginate(20);

        return view('osa-admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('osa-admin.users.create', [
            'organizations' => Organization::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['required', Rule::enum(Role::class)],
            'organization_id' => ['nullable', 'exists:organizations,id', 'required_if:role,org_officer'],
        ]);

        $password = Str::password(12);

        User::create([
            ...$data,
            'organization_id' => $data['role'] === Role::OrgOfficer->value ? $data['organization_id'] : null,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);

        return redirect()->route('osa-admin.users.index')
            ->with('status', "Account created for {$data['email']}. Temporary password: {$password}");
    }
}
