<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\P2pTransferType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreP2pTransferRequest;
use App\Models\P2pTransfer;
use App\Services\P2pTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class P2pTransferController extends Controller
{
    public function __construct(private P2pTransferService $transfers) {}

    public function store(StoreP2pTransferRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $transfer = $this->transfers->transfer(
                $request->user(),
                $data['receiver_phone'],
                (float) $data['amount'],
                P2pTransferType::from($data['type']),
            );
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Transfer failed.', 'errors' => $e->errors()], 422);
        }

        return response()->json([
            'message' => 'Transfer sent.',
            'transfer' => $this->payload($transfer),
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $walletIds = $user->wallet()->pluck('id');

        $sent = P2pTransfer::whereIn('sender_wallet_id', $walletIds)
            ->with('receiver')
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (P2pTransfer $transfer) => $this->payload($transfer));

        return response()->json(['transfers' => $sent]);
    }

    private function payload(P2pTransfer $transfer): array
    {
        return [
            'id' => $transfer->id,
            'reference' => $transfer->reference,
            'amount' => (float) $transfer->amount,
            'fee' => (float) $transfer->fee,
            'type' => $transfer->type->value,
            'status' => $transfer->status->value,
            'created_at' => $transfer->created_at?->toIso8601String(),
            'receiver' => $transfer->receiver ? [
                'id' => $transfer->receiver->id,
                'name' => $transfer->receiver->name,
            ] : null,
        ];
    }
}
