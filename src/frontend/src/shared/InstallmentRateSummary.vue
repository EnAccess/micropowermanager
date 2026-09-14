<template>
  <div style="padding: 1rem">
    <div style="font-size: 1rem; margin: 0; border-bottom: solid 1px #dedede">
      <div>
        {{ $tc("words.cost") }}: {{ moneyFormat(cost) }}
        <br />
      </div>
      <div style="margin-top: 10px">
        {{ $tc("phrases.downPayment") }} : {{ moneyFormat(downPayment) }}
        <br />
      </div>
      <div style="margin-top: 10px">Rates: {{ rateCount }}</div>
    </div>
    <div v-if="showRates">
      <div v-for="rate in parseInt(rateCount)" :key="rate">
        <span v-if="rate < 10" style="opacity: 0">0</span>
        {{ rate }}&nbsp;-&nbsp;{{
          moneyFormat(getRate(rate, rateCount, financedCost))
        }}
      </div>
    </div>
  </div>
</template>

<script>
import { computeRateAmount } from "@/Helpers/applianceRates.js"
import { currency } from "@/mixins/currency.js"

export default {
  name: "InstallmentRateSummary",
  mixins: [currency],
  props: {
    cost: {
      required: true,
    },
    downPayment: {
      required: true,
    },
    rateCount: {
      required: true,
    },
    showRates: {
      type: Boolean,
      default: false,
    },
  },
  computed: {
    financedCost() {
      return Number(this.cost) - Number(this.downPayment)
    },
  },
  methods: {
    getRate: computeRateAmount,
  },
}
</script>
