<template>
  <div>
    <widget
      :id="'ticket-outsource-payout-report'"
      :title="$tc('phrases.ticketOutsourcePayoutReports')"
      :paginator="ticketOursourcePayoutReportsService.paginator"
      :search="false"
      :subscriber="subscriber"
    >
      <md-table v-model="list" md-sort="id" md-sort-order="desc">
        <md-table-row>
          <md-table-head v-for="(item, index) in headers" :key="index">
            {{ item }}
          </md-table-head>
        </md-table-row>
        <md-table-row slot="md-table-row" slot-scope="{ item }">
          <md-table-cell :md-label="$tc('words.date')">
            {{ item.date }}
          </md-table-cell>
          <md-table-cell :md-label="$tc('words.file')">
            <div style="cursor: pointer" @click="download(item.id)">
              <md-icon>save</md-icon>
              <span>{{ $tc("words.download") }}</span>
            </div>
          </md-table-cell>
        </md-table-row>
      </md-table>
    </widget>
  </div>
</template>

<script>
import Widget from "@/shared/Widget.vue"
import { EventBus } from "@/shared/eventbus"
import { TicketOursourcePayoutReportsService } from "@/services/TicketOursourcePayoutReportsService"
import { notify } from "@/mixins/notify"

export default {
  name: "TicketOursourcePayoutReports",
  mixins: [notify],
  components: {
    Widget,
  },
  mounted() {
    EventBus.$on("pageLoaded", this.reloadList)
  },
  beforeDestroy() {
    EventBus.$off("pageLoaded", this.reloadList)
  },
  data() {
    return {
      ticketOursourcePayoutReportsService:
        new TicketOursourcePayoutReportsService(),
      list: [],
      subscriber: "ticketOutsourcePayoutReports",
      headers: [this.$tc("words.date"), this.$tc("words.file")],
    }
  },
  methods: {
    reloadList(subscriber, data) {
      if (subscriber === this.subscriber) {
        this.list = this.ticketOursourcePayoutReportsService.updateList(data)
        EventBus.$emit("widgetContentLoaded", this.subscriber, this.list.length)
      }
    },
    async download(id) {
      try {
        const response =
          await this.ticketOursourcePayoutReportsService.exportTicketOutsourcePayoutReport(
            id,
          )

        const blob = new Blob([response.data])
        const downloadUrl = window.URL.createObjectURL(blob)
        const a = document.createElement("a")
        a.href = downloadUrl

        const contentDisposition = response.headers["content-disposition"]
        const filename =
          contentDisposition?.split("filename=")[1]?.replace(/['"]/g, "") ??
          "report.xlsx"

        a.download = filename
        document.body.appendChild(a)
        a.click()
        a.remove()
        window.URL.revokeObjectURL(downloadUrl)
      } catch (error) {
        console.log("error", error)
        this.alertNotify("error", error.message)
      }
    },
  },
}
</script>

<style scoped></style>
