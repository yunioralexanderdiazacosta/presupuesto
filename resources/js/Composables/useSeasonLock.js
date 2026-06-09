import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Retorna true si la temporada activa está bloqueada.
 * Uso: const isLocked = useSeasonLock();
 *      :disabled="isLocked.value"
 */
export function useSeasonLock() {
    const page = usePage();
    return computed(() => page.props.seasonLocked ?? false);
}
