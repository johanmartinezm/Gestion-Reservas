<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ListReservationsRequest;
use App\Http\Resources\ReservationResource;
use App\Models\User;
use App\Services\ReservationService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserReservationController extends Controller
{
    public function __construct(private readonly ReservationService $reservations) {}

    public function index(ListReservationsRequest $request, User $user): AnonymousResourceCollection
    {
        $tz = config('reservations.timezone');

        $reservations = $this->reservations->listForUser(
            $user,
            CarbonImmutable::parse($request->validated('from'), $tz),
            CarbonImmutable::parse($request->validated('to'), $tz),
        );

        return ReservationResource::collection($reservations);
    }
}
