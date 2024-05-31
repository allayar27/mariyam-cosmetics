<?php

namespace App\Http\Controllers\v1;

use Carbon\Carbon;
use App\Models\v1\User;
use App\Models\v1\Branch;
use App\Models\v1\Device;
use App\Models\v1\Work_Days;
use Illuminate\Http\Request;
use App\Models\v1\Attendance;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\v1\Branch\BranchAddRequest;
use App\Http\Resources\v1\Branch\BranchsResource;

class BranchController extends Controller
{
    public function add(BranchAddRequest $request)
    {
        $data = $request->validated();
        $branch = Branch::create([
            'name' => $data['name'],
            'location' => $data['location'],
        ]);
        $devices = [];
        foreach (['first', 'second'] as $deviceName) {
            $devices[] = [
                'name' => $deviceName,
                'branch_id' => $branch->id
            ];
        }
        Device::insert($devices);
        return response()->json([
            'success' => true,
        ], 201);
    }


    public function update(Request $request, Branch $branch)
    {
        if ($branch) {
            $branch->update([
                'name' => $request->input('name', $branch->name),
                'location' => $request->input('location', $branch->location),
            ]);
            return response()->json([
                'success' => true,
            ]);
        }
    }

    public function all(Request $request)
    {
        $branches = Branch::latest()->get();

        return response()->json([
            'success' => true,
            'data' => BranchsResource::collection($branches)
        ]);
    }

    // public function updateWorkDays()
    // {
    //     // Barcha `Work_Days` yozuvlarini olish
    //     $all = Work_Days::all();

    //     // Filiallar bo'yicha foydalanuvchilar sonini oldindan olish
    //     $branchUsersCount = User::select('branch_id', DB::raw('count(*) as count'))
    //         ->groupBy('branch_id')
    //         ->pluck('count', 'branch_id');

    //     foreach ($all as $days) {
    //         $day = $days->work_day;
    //         $branchId = $days->branch_id;

    //         // Filial uchun foydalanuvchilar sonini olish
    //         $workersCount = Branch::find($branchId)->users->count();

    //         // Ushbu filial va kun uchun barcha kelishlar sonini olish
    //         $allComersCount = Attendance::whereDate('created_at', $day)
    //             ->where('type', 'in')
    //             ->where('branch_id', $branchId)
    //             ->count();

    //         // Ushbu filial va kun uchun kech qolgan ishchilar sonini olish
    //         $lateWorkersCount = Attendance::whereDate('created_at', $day)
    //             ->where('type', 'in')
    //             ->where('branch_id', $branchId)
    //             ->whereHas('user.schedule', function ($scheduleQuery) {
    //                 $scheduleQuery->whereColumn('attendances.time', '>', 'schedules.time_in');
    //             })
    //             ->count();

    //         // Kech qolganlar foizini hisoblash
    //         $percent = $workersCount > 0 ? ($allComersCount * 100 / $workersCount) : 0;

    //         // Type ni aniqlash
    //         $type = $percent > 20 ? 'work_day' : 'none';

    //         // Yozuvni yangilash
    //         $days->update([
    //             'total_workers' => $workersCount,
    //             'workers_count' => $allComersCount,
    //             'late_workers' => $lateWorkersCount,
    //             'type' => $type,
    //         ]);
    //     }

    //     return response()->json([
    //         'success' => true,
    //     ]);
    // }
        public function delete(Branch $branch){
            if($branch){
                $branch->delete();
                return response()->json([
                   'success' => true,
                ]);
            }
        }
   


    
}
