<template>
  <div class="page-container">
    <div class="md-layout md-gutter">
      <div class="md-layout-item md-size-50 md-small-size-100">
        <basic-details :shs="shs" @widget-loaded="handleWidgetLoaded" />
      </div>

      <div
        class="md-layout-item md-size-50 md-small-size-100"
        v-if="hasPersonData"
      >
        <owner
          :person="shs.device.person"
          @widget-loaded="handleWidgetLoaded"
        />
      </div>
    </div>

    <div class="md-layout md-gutter" v-if="hasAddressData">
      <div class="md-layout-item md-size-100">
        <location
          :device="shs.device"
          :serialNumber="shs.serialNumber"
          :id="shs.id"
          @widget-loaded="handleWidgetLoaded"
        />
      </div>
    </div>

    <div class="md-layout md-gutter">
      <div class="md-layout-item md-size-100">
        <transactions
          :transactions="transactions"
          @widget-loaded="handleWidgetLoaded"
        />
      </div>
    </div>
  </div>
</template>

<script>
import BasicDetails from "./BasicDetails.vue"
import Owner from "./Owner.vue"
import Location from "./Location.vue"
import Transactions from "./Transactions.vue"
import {
  SolarHomeSystemService,
  Transactions as TransactionsService,
} from "@/services/SolarHomeSystemService"
import { notify } from "@/mixins"
import { EventBus } from "@/shared/eventbus"

export default {
  name: "SolarHomeSystemDetail",
  mixins: [notify],
  components: {
    BasicDetails,
    Owner,
    Location,
    Transactions,
  },
  data() {
    return {
      serialNumber: this.$route.params.id,
      solarHomeSystemService: new SolarHomeSystemService(),
      shs: {},
      transactions: new TransactionsService(this.$route.params.id),
      loadedWidgets: {
        details: false,
        owner: false,
        location: false,
        transactions: false,
      },
    }
  },
  computed: {
    hasPersonData() {
      return this.shs.device && this.shs.device.person
    },
    hasAddressData() {
      return this.shs.device && this.shs.device.address
    },
  },
  created() {
    this.getSolarHomeSystem()
    this.loadTransactions()
  },
  methods: {
    async getSolarHomeSystem() {
      try {
        const shsData = await this.solarHomeSystemService.getSolarHomeSystem(
          this.serialNumber,
        )
        if (shsData) {
          this.shs = shsData
        }
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    loadTransactions() {
      if (this.transactions && this.transactions.paginator) {
        EventBus.$emit("loadPage", this.transactions.paginator)
      }
    },
    handleWidgetLoaded(widgetName) {
      this.loadedWidgets[widgetName] = true

      switch (widgetName) {
        case "details":
          EventBus.$emit("widgetContentLoaded", "shs-details", 1)
          break
        case "owner":
          EventBus.$emit("widgetContentLoaded", "shs-owner", 1)
          break
        case "location":
          EventBus.$emit("widgetContentLoaded", "shs-location", 1)
          break
        case "transactions":
          EventBus.$emit("widgetContentLoaded", "shs-transactions", 1)
          break
      }
    },
  },
}
</script>

<style scoped>
.page-container {
  padding: 16px;
}
</style>
