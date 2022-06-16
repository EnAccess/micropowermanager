<template>

    <!-- book keeping  reports-->
    <div class="md-layout md-gutter">
        <div class="md-layout-item md-size-50 md-small-size-100">
            <BookKeeping/>
            <!-- monthly reports-->
            <Reports :id="'weekly-report'"
                     :title="$tc('phrases.weeklyReports')"
                     :subscriber="'weeklyReport'"
                     :paginator="reportService.paginatorWeekly"

            />

        </div>
        <div class="md-layout-item md-size-50 md-small-size-100">

            <Reports :id="'monthly-report'"
                     :title="$tc('phrases.monthlyReports')"
                     :subscriber="'monthlyReport'"
                     :paginator="reportService.paginatorMonthly"

            />

        </div>

    </div>


</template>

<script>



import BookKeeping from './BookKeeping'
import Reports from './Reports'
import {ReportsService} from '@/services/ReportsService'

export default {
    name: 'BookKeepingReportList',
    components: { BookKeeping, Reports},

    data() {
        return {
            reportService: new ReportsService(),
        }
    },
    methods: {}
}
</script>

<style scoped>

</style>
