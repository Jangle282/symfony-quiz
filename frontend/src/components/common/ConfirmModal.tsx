interface ConfirmModalProps {
  isOpen: boolean;
  title: string;
  message: string;
  confirmLabel?: string;
  cancelLabel?: string;
  isConfirming?: boolean;
  onCancel: () => void;
  onConfirm: () => void;
  dataCyPrefix?: string;
}

export default function ConfirmModal({
  isOpen,
  title,
  message,
  confirmLabel = 'Confirm',
  cancelLabel = 'Cancel',
  isConfirming = false,
  onCancel,
  onConfirm,
  dataCyPrefix = 'confirm-modal',
}: ConfirmModalProps) {
  if (!isOpen) {
    return null;
  }

  return (
    <div className="modal modal-open" role="dialog" aria-modal="true" data-cy={dataCyPrefix}>
      <div className="modal-box">
        <h3 className="font-bold text-lg" data-cy={`${dataCyPrefix}-title`}>
          {title}
        </h3>
        <p className="py-4" data-cy={`${dataCyPrefix}-message`}>
          {message}
        </p>
        <div className="modal-action">
          <button
            type="button"
            className="btn btn-ghost"
            onClick={onCancel}
            disabled={isConfirming}
            data-cy={`${dataCyPrefix}-cancel`}
          >
            {cancelLabel}
          </button>
          <button
            type="button"
            className="btn btn-error"
            onClick={onConfirm}
            disabled={isConfirming}
            data-cy={`${dataCyPrefix}-confirm`}
          >
            {isConfirming ? <span className="loading loading-spinner loading-sm" /> : confirmLabel}
          </button>
        </div>
      </div>
      <button
        type="button"
        className="modal-backdrop"
        onClick={onCancel}
        aria-label="close modal"
      >
        <span>
          close
        </span>
      </button>
    </div>
  );
}
