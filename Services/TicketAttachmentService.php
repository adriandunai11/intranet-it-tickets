<?php

namespace App\Modules\ItTickets\Services;

use App\Modules\ItTickets\Models\ItTicketAttachmentsModel;

class TicketAttachmentService
{
    private const UPLOAD_BASE_PATH = 'uploads/it_tickets';

    private ItTicketAttachmentsModel $attachmentModel;

    public function __construct(?ItTicketAttachmentsModel $attachmentModel = null)
    {
        $this->attachmentModel = $attachmentModel ?? new ItTicketAttachmentsModel();
    }

    public function uploadMultiple(int $ticketId, array $files, int $uploaderId): bool
    {
        if ($ticketId <= 0 || empty($files)) {
            return false;
        }

        $path = $this->getTicketPath($ticketId);
        $fullPath = FCPATH . $path;

        $this->ensureDirectoryExists($fullPath);

        $uploaded = false;

        foreach ($files as $file) {
            if (!$file || !$file->isValid() || $file->hasMoved()) {
                continue;
            }

            $originalName = $file->getName();
            $file->move($fullPath);

            $this->attachmentModel->create([
                'ticket_id' => $ticketId,
                'path' => $path,
                'filename' => $originalName,
                'created' => date('Y-m-d H:i:s'),
                'uploader' => $uploaderId,
            ]);

            $uploaded = true;
        }

        return $uploaded;
    }

    public function delete(int $attachmentId): array
    {
        $attachment = $this->attachmentModel->getById($attachmentId);

        if (!$attachment) {
            return [
                'status' => false,
                'message' => 'Az állomány nem található.',
                'ticket_id' => null,
            ];
        }

        $ticketId = (int) $attachment->ticket_id;
        $filePath = FCPATH . $attachment->path . '/' . $attachment->filename;

        $deletedFromDatabase = $this->attachmentModel->delete($attachmentId);
        $deletedFromDisk = true;

        if (is_file($filePath)) {
            $deletedFromDisk = unlink($filePath);
        }

        if (!$deletedFromDatabase || !$deletedFromDisk) {
            return [
                'status' => false,
                'message' => 'Az állomány törlése nem sikerült.',
                'ticket_id' => $ticketId,
            ];
        }

        return [
            'status' => true,
            'message' => 'Állomány sikeresen törölve.',
            'ticket_id' => $ticketId,
        ];
    }

    public function find(int $attachmentId): ?object
    {
        $attachment = $this->attachmentModel->getById($attachmentId);

        return $attachment ?: null;
    }

    private function getTicketPath(int $ticketId): string
    {
        return self::UPLOAD_BASE_PATH . '/' . $ticketId;
    }

    private function ensureDirectoryExists(string $fullPath): void
    {
        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0775, true);
        }
    }
}
