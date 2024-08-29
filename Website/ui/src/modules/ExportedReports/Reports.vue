<template>
  <div class="md-layout md-gutter">
    <div class="md-layout-item md-size-50 md-small-size-100">
      <BookKeeping />
      <PeriodicReports
        :id="'weekly-report'"
        :title="$tc('phrases.weeklyReports')"
        :subscriber="'weeklyReport'"
        :paginator="reportService.paginatorWeekly"
      />
    </div>
    <div class="md-layout-item md-size-50 md-small-size-100">
      <PeriodicReports
        :id="'monthly-report'"
        :title="$tc('phrases.monthlyReports')"
        :subscriber="'monthlyReport'"
        :paginator="reportService.paginatorMonthly"
      />
    </div>
  </div>
</template>

<script>
import BookKeeping from "@/modules/ExportedReports/BookKeeping"
import { ReportsService } from "@/services/ReportsService"
import PeriodicReports from "@/modules/ExportedReports/PeriodicReports.vue"

export default {
  name: "Reports",
  components: { PeriodicReports, BookKeeping },
  data() {
    return {
      reportService: new ReportsService(),
    }
  },
}
</script>

<style scoped></style>
