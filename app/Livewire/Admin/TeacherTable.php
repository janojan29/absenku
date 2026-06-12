<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class TeacherTable extends Component
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

        $teachers = User::query()
            ->whereHas('roles', fn($query) => $query->whereIn('name', ['guru', 'guru_walikelas']))
            ->with(['teacher'])
            ->when($q !== '', function ($query) use ($q) {
                $like = '%' . $q . '%';
                $query->where(function ($sub) use ($like) {
                    $sub->where('name', 'like', $like)
                        ->orWhere('whatsapp_number', 'like', $like)
                        ->orWhereHas('teacher', function ($t) use ($like) {
                            $t->where('nip', 'like', $like)
                              ->orWhere('subject', 'like', $like)
                              ->orWhere('wali_kelas', 'like', $like);
                        });
                });
            })
            ->orderBy('name')
            ->paginate(20);

        return view('livewire.admin.teacher-table', [
            'teachers' => $teachers,
        ]);
    }
}
