<template>
   <Reports/>
</template>

<script>
import BookKeeping from '@/modules/ExportedReports/BookKeeping'
import Reports from '@/modules/ExportedReports/Reports'
import PeriodicReports from '@/modules/ExportedReports/PeriodicReports.vue'

export default {
    name: 'ReportsPage',
    components: { PeriodicReports, BookKeeping, Reports }
}
</script>

<style scoped>

</style>
