<template>
  <div>
    <!-- new balance -->
    <md-dialog :md-active.sync="addNewBalance">
      <md-dialog-title>
        {{ $tc("phrases.addBalance") }}
      </md-dialog-title>
      <form
        novalidate
        class="md-layout md-size-100"
        @submit.prevent="saveBalance"
      >
        <div class="md-layout-item md-size-90" style="margin: auto">
          <md-field
            :class="{
              'md-invalid': errors.has($tc('words.amount')),
            }"
          >
            <label for="amount">{{ $tc("words.amount") }}</label>
            <md-input
              :name="$tc('words.amount')"
              id="amount"
              v-model="agentChargeService.balance.amount"
              v-validate="'required|min_value:0'"
              type="number"
            />
            <span class="md-error">
              {{ errors.first($tc("words.amount")) }}
            </span>
          </md-field>
          <md-progress-bar md-mode="indeterminate" v-if="loading" />
        </div>
        <md-dialog-actions
          class="md-layout-item md-size-90"
          style="margin: auto"
        >
          <md-button
            role="button"
            type="submit"
            class="md-raised md-primary"
            :disabled="loading"
          >
            + {{ $tc("words.balance") }}
          </md-button>
          <md-button @click="hide()" class="md-raised md-accent">
            <md-icon>close</md-icon>
            {{ $tc("words.close") }}
          </md-button>
        </md-dialog-actions>
      </form>
    </md-dialog>
  </div>
</template>
<script>
import { AgentChargeService } from "@/services/AgentChargeService"
import { EventBus } from "@/shared/eventbus"

export default {
  name: "addAgentBalance",

  data() {
    return {
      agentChargeService: new AgentChargeService(),

      loading: false,
    }
  },
  props: {
    agentId: {
      default: null,
    },
    addNewBalance: {
      default: false,
    },
  },

  mounted() {},
  beforeDestroy() {},

  methods: {
    async saveBalance() {
      let validator = await this.$validator.validateAll()

      if (validator) {
        this.loading = true
        try {
          this.agentChargeService.balance.agentId = this.agentId

          await this.agentChargeService.addNewBalance()
          this.loading = false
          this.balanceAdded()
          this.alertNotify("success", this.$tc("phrases.addBalance", 1))
        } catch (e) {
          this.loading = false
          this.alertNotify("error", e.message)
        }
      }
    },
    balanceAdded() {
      EventBus.$emit("balanceAdded")
    },
    hide() {
      EventBus.$emit("addBalanceClosed")
    },
  },
}
</script>
<style scoped></style>
