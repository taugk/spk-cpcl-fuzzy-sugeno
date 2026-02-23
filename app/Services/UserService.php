<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Pagination\LengthAwarePaginator;

class UserService
{
    /**
     * Ambil data user dengan pagination.
     * Mengurutkan dari yang terbaru.
     * * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginatedUsers(int $perPage = 10): LengthAwarePaginator
    {
        return User::latest()->paginate($perPage);
    }

    /**
     * Cari user berdasarkan ID.
     * Akan error 404 jika tidak ditemukan.
     * * @param int|string $id
     * @return User
     */
    public function getUserById($id): User
    {
        return User::findOrFail($id);
    }

    /**
     * Logika membuat user baru.
     * Password otomatis di-hash menggunakan Bcrypt.
     * * @param array $data
     * @return User
     */
    public function createUser(array $data): User
    {
        // 1. Hash Password (Bcrypt)
        // Hash::make() otomatis menggunakan algoritma default Laravel (Bcrypt)
        $data['password'] = Hash::make($data['password']);

        // 2. Set default status jika user lupa mengisi
        $data['status'] = $data['status'] ?? 'aktif';

        // 3. Simpan ke Database
        // Note: Kolom 'created_by' akan otomatis diisi oleh UserObserver (jika sudah dipasang)
        return User::create($data);
    }

    /**
     * Logika update user.
     * Menangani update password secara kondisional.
     * * @param User $user
     * @param array $data
     * @return User
     */
    public function updateUser(User $user, array $data): User
    {
        // 1. Cek Logika Password
        if (!empty($data['password'])) {
            // JIKA Admin mengisi password baru: Hash password tersebut
            $data['password'] = Hash::make($data['password']);
        } else {
            // JIKA Kosong/Null: Hapus key 'password' dari array
            // Tujuannya agar password lama di database TIDAK tertimpa/berubah
            unset($data['password']);
        }

        // 2. Update data (kolom lain seperti name, username, role, status akan terupdate)
        $user->update($data);

        return $user;
    }

    /**
     * Logika hapus user.
     * * @param User $user
     * @return bool|null
     */
    public function deleteUser(User $user)
    {
        // Mencegah user menghapus akunnya sendiri (Security Guard)
        // auth()->id() mengambil ID user yang sedang login
        if (Auth::check() && Auth::id() == $user->id) {
            // Anda bisa melempar Exception agar bisa ditangkap Controller
            throw new \Exception('Anda tidak dapat menghapus akun Anda sendiri saat sedang login.');
        }

        return $user->delete();
    }
}