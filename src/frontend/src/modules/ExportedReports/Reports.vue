<template>
  <div>
    <unauthorized-page v-if="!canViewReports" />
    <div v-else class="md-layout md-gutter">
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
  </div>
</template>

<script>
import TicketOursourcePayoutReports from "@/modules/ExportedReports/TicketOursourcePayoutReports"
import { ReportsService } from "@/services/ReportsService"
import VillageReports from "@/modules/ExportedReports/VillageReports.vue"
import UnauthorizedPage from "@/pages/Unauthorized/index.vue"
import { mapGetters } from "vuex"

export default {
  name: "Reports",
  components: {
    VillageReports,
    TicketOursourcePayoutReports,
    UnauthorizedPage,
  },
  data() {
    return {
      reportService: new ReportsService(),
    }
  },
  computed: {
    ...mapGetters({
      userPermissions: "auth/getPermissions",
    }),
    canViewReports() {
      return this.userPermissions.includes("reports")
    },
  },
}
</script>

<style scoped></style>
