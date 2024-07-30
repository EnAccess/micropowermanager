<template>
  <div class="page-container" id="widget-grid">
    <div class="md-layout">
      <div class="md-layout-item md-size-100 md-small-size-100">
        <div class="md-layout md-gutter md-size-100">
          <div class="md-layout-item md-layout-size-50 md-small-size-100">
            <meter-basic v-if="showDetails" :meter="meterDetailService.meter" />
          </div>
          <div class="md-layout-item md-layout-size-50 md-small-size-100">
            <meter-details
              v-if="showDetails"
              :meter="meterDetailService.meter"
              @updated="updateMeterDetails"
            />
          </div>
        </div>
      </div>
      <div class="md-layout-item md-size-100 md-small-size-100">
        <meter-transactions :transactions="transactions" />
      </div>
      <div class="md-layout-item md-size-100 md-small-size-100">
        <meter-readings
          v-if="showMeterReadings"
          :meter="meterDetailService.meter"
        />
      </div>
    </div>
  </div>
</template>

<script>
import { Transactions } from "@/services/TransactionService"
import { MeterDetailService } from "@/services/MeterDetailService"
import MeterBasic from "@/modules/Meter/Basic"
import MeterDetails from "@/modules/Meter/Details"
import MeterTransactions from "@/modules/Meter/Transactions"
import MeterReadings from "@/modules/Meter/Readings"
import { notify } from "@/mixins/notify"

export default {
  name: "Meter",
  mixins: [notify],
  components: { MeterBasic, MeterDetails, MeterTransactions, MeterReadings },
  data() {
    return {
      serialNumber: this.$route.params.id,
      meterDetailService: new MeterDetailService(),
      transactions: null,
    }
  },
  created() {
    this.getMeterDetails()
    this.transactions = new Transactions(this.$route.params.id)
  },
  methods: {
    async getMeterDetails() {
      try {
        await this.meterDetailService.getDetail(this.serialNumber)
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async updateMeterDetails(meterDetail) {
      try {
        await this.meterDetailService.updateMeterDetails(meterDetail)
        this.alertNotify("success", this.$t("phrases.successfullyUpdated"))
        await this.getMeterDetails()
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
  },
  computed: {
    showMeterReadings() {
      if (!this.meterDetailService.meter.meterType) {
        return false
      } else return this.meterDetailService.meter.meterType.online === 1
    },
    showDetails() {
      return this.meterDetailService.meter.loaded === true
    },
  },
}
</script>

<style lang="scss">
.md-menu-content {
  z-index: 11 !important;
}

.asd__inner-wrapper {
  margin-left: 0 !important;
}

.asd__wrapper--datepicker-open {
  right: 20px !important;
}

.mt-15 {
  margin-top: 15px;
}

.list-container {
  max-height: 200px;
  overflow: hidden;
  overflow-y: scroll;
}

.list-item {
  padding: 20px;
  margin: 0.5rem 0;
  cursor: pointer;
  border-bottom: 1px dotted;
}

.list-item-info {
  padding: 5px;
  color: #514e50;
  font-size: 0.8rem;
}

.list-item:hover {
  color: white;
  background-color: rgba(15, 15, 15, 0.8);
}

.md-autocomplete-item {
  z-index: 110;
}

.meter-overview-detail {
  margin-top: 1vh;
}

.meter-overview-card {
  min-height: 195px;
}
</style>
