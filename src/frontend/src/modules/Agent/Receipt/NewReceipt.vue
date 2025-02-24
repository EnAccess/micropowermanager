<template>
  <md-dialog :md-active.sync="addNewReceipt" :md-clicked-outside="true">
    <div v-if="agent.balance < 0">
      <form novalidate class="md-layout" @submit.prevent="saveReceipt">
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
                <md-icon style="color: green">done</md-icon>
              </span>
              <div class="md-layout-item md-size-100 exclamation-div">
                <span>
                  {{
                    $tc("phrases.addReceiptNotify", 1, {
                      energySupplier: agent.dueToEnergySupplier,
                    })
                  }}
                </span>
              </div>
            </div>
            <md-field
              :class="{
                'md-invalid': errors.has($tc('words.amount')),
              }"
            >
              <label>{{ $tc("words.amount") }}</label>
              <md-input
                :name="$tc('words.amount')"
                id="amount"
                v-model="agentReceiptService.newReceipt.amount"
                :max="agent.dueToEnergySupplier"
                v-validate="'required|min_value:0'"
                type="number"
              />
              <span class="md-error">
                {{ errors.first($tc("words.amount")) }}
              </span>
            </md-field>
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
          </div>
        </md-card-content>
        <md-card-actions></md-card-actions>
      </md-card>
    </div>
  </md-dialog>
</template>
<script>
import { EventBus } from "@/shared/eventbus"
import { AgentReceiptService } from "@/services/AgentReceiptService"
import { AgentService } from "@/services/AgentService"
import { notify } from "@/mixins/notify"

export default {
  name: "NewReceipt",
  mixins: [notify],
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
  methods: {
    async saveReceipt() {
      if (
        this.agentReceiptService.newReceipt.amount >
        this.agent.dueToEnergySupplier
      ) {
        this.alertNotify(
          "warn",
          this.$tc("phrases.addReceiptNotify", 2, {
            dueToEnergySupplier: this.agent.dueToEnergySupplier,
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
<style scoped></style>
<style scoped>
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
