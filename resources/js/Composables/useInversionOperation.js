/**
 * Determina si una operación (por id) corresponde a "Inversión".
 * Uso: const { isInversionOp } = useInversionOperation();
 *      isInversionOp(form.operation_id, props.operations)
 */
export function useInversionOperation() {
    const isInversionOp = (operationId, operationsList) => {
        if (!operationId) return false;
        const op = (operationsList || []).find(o => String(o.value) === String(operationId));
        return op ? /invers/i.test(op.label) : false;
    };

    return { isInversionOp };
}
