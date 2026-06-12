<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class StudentTable extends Component
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

        $students = User::query()
            ->role('siswa')
            ->with(['studentProfile.classRoom'])
            ->when($q !== '', function ($query) use ($q) {
                $like = '%' . $q . '%';
                $query->where(function ($sub) use ($like) {
                    $sub->where('name', 'like', $like)
                        ->orWhereHas('studentProfile', function ($sp) use ($like) {
                            $sp->where('nis', 'like', $like)
                              ->orWhere('jurusan', 'like', $like)
                              ->orWhere('parent_phone_wa', 'like', $like)
                              ->orWhereHas('classRoom', function ($cr) use ($like) {
                                  $cr->where('name', 'like', $like)
                                    ->orWhere('jurusan', 'like', $like);
                              });
                        });
                });
            })
            ->orderBy('name')
            ->paginate(20);

        return view('livewire.admin.student-table', [
            'students' => $students,
        ]);
    }
}
