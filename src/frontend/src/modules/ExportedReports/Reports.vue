<template>
  <div class="md-layout md-gutter">
    <div class="md-layout-item md-size-50 md-small-size-100">
      <VillageReports
        :id="'monthly-report'"
        :title="$tc('phrases.villageReportsMonthly')"
        :subscriber="'monthlyReport'"
        :paginator="reportService.paginatorMonthly"
      />
    </div>
    <div class="md-layout-item md-size-50 md-small-size-100">
      <VillageReports
        :id="'weekly-report'"
        :title="$tc('phrases.villageReportsWeekly')"
        :subscriber="'weeklyReport'"
        :paginator="reportService.paginatorWeekly"
      />
    </div>
    <div class="md-layout-item md-size-50 md-small-size-100">
      <TicketOursourcePayoutReports />
    </div>
  </div>
</template>

<script>
import TicketOursourcePayoutReports from "@/modules/ExportedReports/TicketOursourcePayoutReports"
import { ReportsService } from "@/services/ReportsService"
import VillageReports from "@/modules/ExportedReports/VillageReports.vue"

export default {
  name: "Reports",
  components: { VillageReports, TicketOursourcePayoutReports },
  data() {
    return {
      reportService: new ReportsService(),
    }
  },
}
</script>

<style scoped></style>
