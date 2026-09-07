<template>
  <md-dialog :md-active.sync="addNewReceipt" :md-clicked-outside="true">
    <div v-if="agentBalance > 0">
      <form novalidate class="md-layout" @submit.prevent="saveReceipt">
        <md-card class="md-layout-item">
          <md-card-header>
            <div style="float: right; cursor: pointer" @click="hide()">
              <md-icon>close</md-icon>
              &nbsp;{{ $tc("words.close") }}
            </div>
          </md-card-header>
          <md-card-content>
            <div class="receipt-summary">
              <div class="receipt-summary-row">
                <span>{{ $tc("phrases.dueToCompany") }}</span>
                <strong>{{ moneyFormat(agentBalance) }}</strong>
              </div>
              <div class="receipt-summary-row">
                <span>{{ $tc("phrases.pendingCommission") }}</span>
                <strong>{{ moneyFormat(pendingCommission) }}</strong>
              </div>
            </div>
            <md-field
              :class="{
                'md-invalid': errors.has($tc('words.amount')),
              }"
            >
              <label>{{ $tc("phrases.amountCollected") }}</label>
              <md-input
                :name="$tc('words.amount')"
                id="amount"
                v-model="agentReceiptService.newReceipt.amount"
                :max="agentBalance"
                v-validate="amountRules"
                type="number"
              />
              <span class="md-error">
                {{ errors.first($tc("words.amount")) }}
              </span>
            </md-field>
            <div class="receipt-summary">
              <div class="receipt-summary-row">
                <span>{{ $tc("phrases.commissionCredited") }}</span>
                <strong>&minus; {{ moneyFormat(commissionCredited) }}</strong>
              </div>
              <div class="receipt-summary-row">
                <span>{{ $tc("phrases.amountReceived") }}</span>
                <strong>{{ moneyFormat(amountReceived) }}</strong>
              </div>
              <span class="receipt-hint">
                {{ $tc("phrases.commissionCreditedHint") }}
              </span>
            </div>
            <md-progress-bar md-mode="indeterminate" v-if="loading" />
          </md-card-content>
          <md-card-actions>
            <md-button
              role="button"
              type="submit"
              class="md-raised md-primary"
              :disabled="loading"
            >
              {{ $tc("words.receive") }}
            </md-button>
          </md-card-actions>
        </md-card>
      </form>
    </div>
    <div v-else>
      <md-card class="md-layout-item">
        <md-card-header>
          <div style="float: right; cursor: pointer" @click="hide()">
            <md-icon>close</md-icon>
            &nbsp;{{ $tc("words.close") }}
          </div>
        </md-card-header>
        <md-card-content>
          <div class="exclamation">
            <span class="success-span">
              <md-icon style="color: green">notifications</md-icon>
            </span>
            <div class="md-layout-item md-size-100 exclamation-div">
              <span>{{ $tc("phrases.addReceipt", 2) }}</span>
            </div>
            <div
              v-if="agentBalance < 0"
              class="md-layout-item md-size-100 exclamation-div"
            >
              <span>
                {{
                  $tc("phrases.companyOwesAgent", 1, {
                    amount: moneyFormat(-agentBalance),
                  })
                }}
              </span>
            </div>
            <div
              v-if="pendingCommission > 0"
              class="md-layout-item md-size-100 exclamation-div"
            >
              <span>
                {{
                  $tc("phrases.pendingCommissionNotPayable", 1, {
                    commission: moneyFormat(pendingCommission),
                  })
                }}
              </span>
            </div>
          </div>
        </md-card-content>
        <md-card-actions></md-card-actions>
      </md-card>
    </div>
  </md-dialog>
</template>
<script>
import { currency } from "@/mixins/currency.js"
import { notify } from "@/mixins/notify.js"
import { AgentReceiptService } from "@/services/AgentReceiptService.js"
import { AgentService } from "@/services/AgentService.js"
import { EventBus } from "@/shared/eventbus.js"

export default {
  name: "NewReceipt",
  mixins: [notify, currency],
  data() {
    return {
      agentReceiptService: new AgentReceiptService(),
      loading: false,
      agentService: new AgentService(),
    }
  },
  components: {},
  props: {
    agent: {},
    addNewReceipt: {
      type: Boolean,
      default: false,
    },
  },
  watch: {
    addNewReceipt(opened) {
      if (opened) {
        this.agentReceiptService.newReceipt.amount = this.agentBalance
      }
    },
  },
  computed: {
    agentBalance() {
      return Number(this.agent.balance || 0)
    },
    pendingCommission() {
      return Number(this.agent.commissionRevenue || 0)
    },
    amountCollected() {
      return Number(this.agentReceiptService.newReceipt.amount || 0)
    },
    amountRules() {
      return `required|min_value:0.01|max_value:${Math.max(0, this.agentBalance)}`
    },
    // Mirrors AgentReceiptObserver: the agent keeps their commission out of the
    // cash they hand over, so it is credited once they have settled everything
    // they hold, and never for more than the cash on the table.
    commissionCredited() {
      if (this.amountCollected <= 0 || this.agentBalance <= 0) return 0
      const collected = Math.round(this.amountCollected * 100)
      const due = Math.round(this.agentBalance * 100)
      if (collected < due) return 0
      return Math.min(this.pendingCommission, this.amountCollected)
    },
    amountReceived() {
      return this.amountCollected - this.commissionCredited
    },
  },
  methods: {
    async saveReceipt() {
      if (this.amountCollected > this.agentBalance) {
        this.alertNotify(
          "warn",
          this.$tc("phrases.addReceiptNotify", 2, {
            balance: this.moneyFormat(this.agentBalance),
          }),
        )
      } else {
        let validator = await this.$validator.validateAll()
        if (validator) {
          try {
            this.loading = true
            try {
              this.agentReceiptService.newReceipt.agentId = this.agent.id
              await this.agentReceiptService.addNewReceipt()
              this.loading = false
              this.receiptAdded()
              this.alertNotify("success", this.$tc("phrases.addReceipt", 1))
            } catch (e) {
              this.loading = false
              this.alertNotify("error", e.message)
            }
          } catch (e) {
            this.alertNotify("error", e.message)
          }
        }
      }
    },
    hide() {
      EventBus.$emit("newReceiptClosed")
    },
    receiptAdded() {
      EventBus.$emit("receiptAdded")
    },
  },
}
</script>
<style scoped lang="scss">
.receipt-summary {
  margin-bottom: 1rem;
}

.receipt-summary-row {
  display: flex;
  justify-content: space-between;
  padding: 0.35rem 0;
  border-bottom: 1px solid rgba(0, 0, 0, 0.12);
}

.receipt-hint {
  display: block;
  padding-top: 0.5rem;
  font-size: 0.75rem;
  color: rgba(0, 0, 0, 0.54);
}

.success-span {
  font-size: large;
  font-weight: 700;
  color: green;
}

.exclamation-div {
  margin-top: 2% !important;
}

.exclamation {
  width: 100% !important;
  margin: auto;
  align-items: center;
  display: inline-grid;
  text-align: center;
}

.exclamation-div span {
  font-size: medium !important;
}
</style>
