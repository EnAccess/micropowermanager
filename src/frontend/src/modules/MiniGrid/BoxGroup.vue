<template>
  <div class="md-layout md-gutter md-size-100">
    <div class="md-layout-item md-medium-size-50 md-xsmall-size-100 md-size-33">
      <box
        v-if="miniGridData.soldEnergy"
        :box-color="'orange'"
        :center-text="true"
        :header-text="$tc('phrases.soldEnergy')"
        :sub-text="miniGridData.soldEnergy.data.toString() + 'kWh'"
        :box-icon="'wb_iridescent'"
      />
    </div>
    <div class="md-layout-item md-medium-size-50 md-xsmall-size-100 md-size-33">
      <box
        v-if="miniGridData.transactions"
        :box-color="'red'"
        :center-text="true"
        :header-text="$tc('phrases.processedTransactions')"
        :sub-text="moneyFormat(miniGridData.transactions[0].amount)"
        :box-icon="'list'"
      />
    </div>
    <div class="md-layout-item md-medium-size-50 md-xsmall-size-100 md-size-33">
      <box
        v-if="miniGridData.transactions"
        :box-color="'green'"
        :center-text="true"
        :header-text="$tc('words.revenue')"
        :sub-text="
          readable(miniGridData.transactions[0].revenue).toString() +
          $store.getters['settings/getMainSettings'].currency
        "
        :box-icon="'attach_money'"
      />
    </div>
  </div>
</template>

<script>
import { MiniGridService } from "@/services/MiniGridService"
import Box from "@/shared/Box.vue"
import { currency } from "@/mixins/currency"

export default {
  name: "BoxGroup",
  components: { Box },
  mixins: [currency],
  props: {
    miniGridId: {
      required: true,
    },
    miniGridData: {
      required: true,
    },
  },
  data() {
    return {
      miniGridService: new MiniGridService(),
      soldEnergy: 0,
      currentTransaction: null,
    }
  },
  methods: {
    async getTransactionsOverview(startDate, endDate) {
      try {
        this.currentTransaction =
          await this.miniGridService.getTransactionsOverview(
            this.miniGridId,
            startDate,
            endDate,
          )
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async getSoldEnergy(startDate, endDate) {
      try {
        this.soldEnergy = await this.miniGridService.getSoldEnergy(
          this.miniGridId,
          startDate,
          endDate,
        )
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
  },
}
</script>

<style scoped></style>
