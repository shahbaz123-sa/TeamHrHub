import { computed } from 'vue'
import { hasRole } from '@/utils/permission'

/**
 * Returns a computed ref indicating if extra filters should be shown.
 * Logic: show if user is manager, hr, or super admin.
 * Usage: const showExtraFilters = useShowExtraFilters()
 */
export default function useShowExtraFilters() {
  return computed(() =>
    hasRole('Manager') || hasRole('Hr')
  )
}

