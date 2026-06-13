<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataTransferObjects\CreateReservationData;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;

class ReservationController extends Controller
{
    public function __construct(private readonly ReservationService $reservations) {}

    public function store(StoreReservationRequest $request): JsonResponse
    {
        $reservation = $this->reservations->create(
            CreateReservationData::fromArray($request->validated()),
        );

        return ReservationResource::make($reservation)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Reservation $reservation): ReservationResource
    {
        return ReservationResource::make($reservation);
    }

    public function cancel(Reservation $reservation): ReservationResource
    {
        return ReservationResource::make(
            $this->reservations->cancel($reservation),
        );
    }
}
