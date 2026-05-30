<template>
  <md-dialog
    :md-active="show"
    @update:mdActive="onActiveChange"
    style="max-width: 40rem; margin: auto"
  >
    <md-dialog-title>{{ $tc("phrases.modifyPaymentPlan") }}</md-dialog-title>
    <md-dialog-content>
      <form class="md-layout md-gutter">
        <div class="md-layout-item md-size-100">
          <md-field
            :class="{ 'md-invalid': errors.has($tc('phrases.totalCost')) }"
          >
            <label>{{ $tc("phrases.totalCost") }}</label>
            <span class="md-prefix">{{ currency }}</span>
            <md-input
              type="number"
              v-model="newTotalCost"
              :name="$tc('phrases.totalCost')"
              v-validate="'required|decimal|min_value:0'"
            />
            <span class="md-error">
              {{ errors.first($tc("phrases.totalCost")) }}
            </span>
          </md-field>
        </div>
        <div class="md-layout-item md-size-50 md-small-size-100">
          <md-field>
            <label>{{ $tc("phrases.rateType") }}</label>
            <md-select v-model="rateType" name="rate_type">
              <md-option value="weekly">{{ $tc("words.week", 2) }}</md-option>
              <md-option value="monthly">{{ $tc("words.month", 2) }}</md-option>
            </md-select>
          </md-field>
        </div>
        <div class="md-layout-item md-size-50 md-small-size-100">
          <md-field
            :class="{ 'md-invalid': errors.has($tc('phrases.ratesCount')) }"
          >
            <label>{{ $tc("phrases.ratesCount") }}</label>
            <md-input
              type="number"
              v-model="rateCount"
              :name="$tc('phrases.ratesCount')"
              v-validate="'required|integer|min_value:1'"
            />
            <span class="md-error">
              {{ errors.first($tc("phrases.ratesCount")) }}
            </span>
          </md-field>
        </div>
      </form>

      <md-content class="md-accent below-paid" v-if="belowPaid">
        {{ $tc("phrases.totalCostBelowPaid") }}
      </md-content>

      <div class="plan-summary" v-else>
        <div>
          <b>{{ $tc("phrases.totalCost") }}:</b>
          {{ moneyFormat(newTotalCost || 0) }}
        </div>
        <div>
          <b>{{ $tc("phrases.alreadyPaid") }}:</b>
          {{ moneyFormat(paidAmount) }}
        </div>
        <div>
          <b>{{ $tc("phrases.outstandingAmount") }}:</b>
          {{ moneyFormat(newOutstanding) }}
        </div>
        <div>
          <b>{{ $tc("phrases.ratesCount") }}:</b>
          {{ rateCount }}
        </div>
        <div v-if="showRates" class="rates-detail">
          <div v-for="rate in ratesPreview" :key="rate.index">
            <span v-if="rate.index < 10" style="opacity: 0">0</span>
            {{ rate.index }}&nbsp;-&nbsp;{{ moneyFormat(rate.amount) }}
            <span class="rate-date">({{ formatReadableDate(rate.date) }})</span>
          </div>
        </div>
      </div>
    </md-dialog-content>
    <md-dialog-actions>
      <md-button
        v-if="showRatesButton"
        class="md-accent md-raised"
        @click="showRates = !showRates"
      >
        Show Rates Detail
      </md-button>
      <md-button class="md-dense md-raised" @click="close">
        {{ $tc("words.cancel") }}
      </md-button>
      <md-button
        class="md-primary md-dense md-raised"
        :disabled="saving || belowPaid"
        @click="save"
      >
        {{ $tc("words.save") }}
      </md-button>
    </md-dialog-actions>
  </md-dialog>
</template>

<script>
import moment from "moment"

import { computeRateAmount } from "@/Helpers/applianceRates.js"
import { currency } from "@/mixins/currency.js"

export default {
  name: "ModifyTotalCostModal",
  mixins: [currency],
  props: {
    show: {
      type: Boolean,
      required: true,
    },
    soldAppliance: {
      type: Object,
      required: true,
    },
    saving: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      newTotalCost: null,
      rateType: "monthly",
      rateCount: null,
      showRates: false,
      currency: this.$store.getters["settings/getMainSettings"].currency,
    }
  },
  computed: {
    rates() {
      return this.soldAppliance.rates || []
    },
    paidAmount() {
      return this.rates.reduce(
        (sum, rate) => sum + (this.rateCostOf(rate) - this.remainingOf(rate)),
        0,
      )
    },
    unpaidRates() {
      return this.rates
        .filter(
          (rate) =>
            this.rateCostOf(rate) === this.remainingOf(rate) &&
            this.rateCostOf(rate) > 0,
        )
        .slice()
        .sort(
          (a, b) => new Date(this.dueDateOf(a)) - new Date(this.dueDateOf(b)),
        )
    },
    currentOutstandingCount() {
      return this.unpaidRates.length
    },
    inferredSchedule() {
      const dates = this.rates
        .map((rate) => this.dueDateOf(rate))
        .filter(Boolean)
        .map((date) => new Date(date))
        .sort((a, b) => a - b)
      if (dates.length < 2) return "monthly"
      const gapInDays = (dates[1] - dates[0]) / (1000 * 60 * 60 * 24)
      return gapInDays <= 10 ? "weekly" : "monthly"
    },
    anchorDate() {
      const settledDates = this.rates
        .filter((rate) => this.rateCostOf(rate) !== this.remainingOf(rate))
        .map((rate) => this.dueDateOf(rate))
        .filter(Boolean)
        .map((date) => new Date(date))
      if (settledDates.length) return new Date(Math.max(...settledDates))
      if (this.soldAppliance.firstPaymentDate) {
        return new Date(this.soldAppliance.firstPaymentDate)
      }
      return new Date()
    },
    newOutstanding() {
      return Math.max(0, Number(this.newTotalCost || 0) - this.paidAmount)
    },
    countChanged() {
      return parseInt(this.rateCount) !== this.currentOutstandingCount
    },
    scheduleChanged() {
      return this.rateType !== this.inferredSchedule
    },
    willRegenerate() {
      return this.countChanged || this.scheduleChanged
    },
    ratesPreview() {
      const count = parseInt(this.rateCount) || 0
      const outstanding = this.newOutstanding
      if (count < 1 || outstanding <= 0) return []
      if (!this.willRegenerate) {
        return this.unpaidRates.map((rate, index) => ({
          index: index + 1,
          amount: computeRateAmount(index + 1, count, outstanding),
          date: this.dueDateOf(rate),
        }))
      }
      const unit = this.rateType === "weekly" ? "weeks" : "months"
      return Array.from({ length: count }, (_, index) => ({
        index: index + 1,
        amount: computeRateAmount(index + 1, count, outstanding),
        date: moment(this.anchorDate)
          .add(index + 1, unit)
          .toDate(),
      }))
    },
    showRatesButton() {
      return parseInt(this.rateCount) > 1 && this.ratesPreview.length > 0
    },
    belowPaid() {
      return (
        this.newTotalCost !== null &&
        this.newTotalCost !== "" &&
        Number(this.newTotalCost) < this.paidAmount
      )
    },
  },
  watch: {
    show(value) {
      if (value) this.initFromAppliance()
    },
  },
  created() {
    this.initFromAppliance()
  },
  methods: {
    rateCostOf(rate) {
      return Number(rate.rateCost ?? rate.rate_cost ?? 0)
    },
    remainingOf(rate) {
      return Number(rate.remaining ?? 0)
    },
    dueDateOf(rate) {
      return rate.dueDate || rate.due_date
    },
    formatReadableDate(date) {
      return moment(date).format("LL")
    },
    initFromAppliance() {
      this.newTotalCost = this.soldAppliance.totalCost
      this.rateCount = this.currentOutstandingCount
      this.rateType = this.inferredSchedule
      this.showRates = false
    },
    onActiveChange(value) {
      if (!value) this.close()
    },
    close() {
      this.$emit("close")
    },
    async save() {
      const valid = await this.$validator.validateAll()
      if (!valid || this.belowPaid) return
      const changed = this.willRegenerate
      this.$emit("save", {
        newTotalCost: parseInt(this.newTotalCost),
        rateCount: changed ? parseInt(this.rateCount) : null,
        rateType: changed ? this.rateType : null,
      })
    },
  },
}
</script>

<style scoped lang="scss">
.plan-summary {
  padding: 1rem 0 0;
  font-size: 1rem;

  > div {
    margin-top: 0.35rem;
  }
}

.rates-detail {
  margin-top: 0.75rem;
  border-top: solid 1px #dedede;
  padding-top: 0.5rem;

  .rate-date {
    color: #888;
    font-size: 0.85rem;
  }
}

.below-paid {
  margin-top: 1rem;
  padding: 0.5rem 0.75rem;
  border-radius: 2px;
}
</style>
