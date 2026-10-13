<?php

namespace Modules\User\Enums;

enum ProfessionalHistoryAction: string
{
    case DossierSubmitted = 'dossier_submitted';
    case DocumentValidated = 'document_validated';
    case DocumentRejected = 'document_rejected';
    case DocumentReplaced = 'document_replaced';
    case DossierValidated = 'dossier_validated';
    case DossierRejected = 'dossier_rejected';
    case SensitiveChangesSubmitted = 'sensitive_changes_submitted';
    case SensitiveChangesApproved = 'sensitive_changes_approved';
    case SensitiveChangesRejected = 'sensitive_changes_rejected';
    case ServicesUpdatedByAdmin = 'services_updated_by_admin';
    case AvailabilityChanged = 'availability_changed';
    case ProfileUpdated = 'profile_updated';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::DossierSubmitted => 'Soumission du dossier',
            self::DocumentValidated => 'Validation d\'un document',
            self::DocumentRejected => 'Rejet d\'un document',
            self::DocumentReplaced => 'Remplacement d\'un document',
            self::DossierValidated => 'Validation du dossier',
            self::DossierRejected => 'Rejet du dossier',
            self::SensitiveChangesSubmitted => 'Demande de modifications sensibles',
            self::SensitiveChangesApproved => 'Validation des modifications sensibles',
            self::SensitiveChangesRejected => 'Rejet des modifications sensibles',
            self::ServicesUpdatedByAdmin => 'Mise à jour des prestations par l\'administration',
            self::AvailabilityChanged => 'Changement de disponibilité',
            self::ProfileUpdated => 'Mise à jour du profil',
        };
    }
}
