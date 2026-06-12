<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class UserTable extends Component
{
    use WithPagination;

    public function paginationView()
    {
        return 'vendor.livewire.custom-tailwind';
    }

    public string $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $q = trim($this->search);

        $users = User::query()
            ->with(['studentProfile.classRoom', 'teacher'])
            ->when($q !== '', function ($query) use ($q) {
                $like = '%' . $q . '%';
                $query->where(function ($sub) use ($like) {
                    $sub->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('whatsapp_number', 'like', $like)
                        ->orWhereHas('roles', fn($r) => $r->where('name', 'like', $like))
                        ->orWhereHas('studentProfile', function ($sp) use ($like) {
                            $sp->where('nis', 'like', $like)
                              ->orWhere('jurusan', 'like', $like)
                              ->orWhere('parent_phone_wa', 'like', $like)
                              ->orWhereHas('classRoom', function ($cr) use ($like) {
                                  $cr->where('name', 'like', $like)
                                    ->orWhere('jurusan', 'like', $like);
                              });
                        })
                        ->orWhereHas('teacher', function ($t) use ($like) {
                            $t->where('nip', 'like', $like)
                              ->orWhere('subject', 'like', $like)
                              ->orWhere('wali_kelas', 'like', $like);
                        });
                });
            })
            ->orderBy('name')
            ->paginate(20);

        return view('livewire.admin.user-table', [
            'users' => $users,
        ]);
    }
}
