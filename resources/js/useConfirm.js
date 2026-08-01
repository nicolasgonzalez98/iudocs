import { reactive } from 'vue';

// Estado compartido (singleton) del modal de confirmación.
export const confirmState = reactive({
    show: false,
    title: '',
    message: '',
    confirmText: 'Confirmar',
    cancelText: 'Cancelar',
    danger: false,
    _resolve: null,
});

// Composable: `const { confirm } = useConfirm()` → `if (await confirm({...})) {...}`
export function useConfirm() {
    const confirm = (opts = {}) => {
        confirmState.title = opts.title ?? '¿Estás segura?';
        confirmState.message = opts.message ?? '';
        confirmState.confirmText = opts.confirmText ?? 'Confirmar';
        confirmState.cancelText = opts.cancelText ?? 'Cancelar';
        confirmState.danger = opts.danger ?? false;
        confirmState.show = true;

        return new Promise((resolve) => {
            confirmState._resolve = resolve;
        });
    };

    return { confirm };
}

// Resuelve la promesa y cierra el modal (lo usa ConfirmModal).
export function settleConfirm(value) {
    confirmState.show = false;
    if (confirmState._resolve) {
        confirmState._resolve(value);
        confirmState._resolve = null;
    }
}
