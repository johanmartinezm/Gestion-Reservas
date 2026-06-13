<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AvailabilityRequest;
use App\Models\Professional;
use App\Models\Service;
use App\Services\SlotFinder;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

class ProfessionalAvailabilityController extends Controller
{
    public function __construct(private readonly SlotFinder $slotFinder) {}

    public function index(AvailabilityRequest $request, Professional $professional): JsonResponse
    {
        $service = Service::query()->findOrFail($request->validated('service_id'));
        $date = CarbonImmutable::parse($request->validated('date'), config('reservations.timezone'));

        $slots = $this->slotFinder->freeSlots($professional->id, $date, $service->duration_minutes);

        return response()->json([
            'data' => [
                'professional_id' => $professional->id,
                'service_id' => $service->id,
                'date' => $date->toDateString(),
                'duration_minutes' => $service->duration_minutes,
                'slots' => $slots,
            ],
        ]);
    }
}
