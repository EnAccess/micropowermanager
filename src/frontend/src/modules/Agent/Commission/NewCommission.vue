<template>
  <widget v-if="addNewCommission" :title="$tc('phrases.addCommissionType')">
    <form novalidate class="md-layout" @submit.prevent="saveCommission">
      <md-card class="md-layout-item">
        <md-card-header>
          <div style="float: right; cursor: pointer" @click="hide()">
            <md-icon>close</md-icon>
            &nbsp;{{ $tc("words.close") }}
          </div>
        </md-card-header>
        <md-card-content>
          <md-field :class="{ 'md-invalid': errors.has($tc('words.name')) }">
            <label>{{ $tc("words.name") }}</label>
            <md-input
              :name="$tc('words.name')"
              id="name"
              v-model="agentCommissionService.agentCommission.name"
              v-validate="'required|min:3'"
            />
            <span class="md-error">
              {{ errors.first($tc("words.name")) }}
            </span>
          </md-field>

          <md-field
            :class="{
              'md-invalid': errors.has($tc('phrases.energyCommission')),
            }"
          >
            <label>{{ $tc("phrases.energyCommission") }}</label>
            <md-input
              :name="$tc('phrases.energyCommission')"
              id="energyCommission"
              v-model="agentCommissionService.agentCommission.energyCommission"
              v-validate="'required|min_value:0'"
              type="number"
            />
            <span class="md-error">
              {{ errors.first($tc("phrases.energyCommission")) }}
            </span>
          </md-field>

          <md-field
            :class="{
              'md-invalid': errors.has($tc('phrases.applianceCommission')),
            }"
          >
            <label>{{ $tc("phrases.applianceCommission") }}</label>
            <md-input
              :name="$tc('phrases.applianceCommission')"
              id="applianceCommission"
              v-model="
                agentCommissionService.agentCommission.applianceCommission
              "
              v-validate="'required|min_value:0'"
              type="number"
            />
            <span class="md-error">
              {{ errors.first($tc("phrases.applianceCommission")) }}
            </span>
          </md-field>

          <md-field
            :class="{
              'md-invalid': errors.has($tc('phrases.riskBalance')),
            }"
          >
            <label>
              {{ $tc("phrases.riskBalance") }} ({{
                $tc("phrases.mustBeNegative")
              }})
            </label>
            <md-input
              :name="$tc('phrases.riskBalance')"
              id="riskBalance"
              max="0"
              v-model="agentCommissionService.agentCommission.riskBalance"
              v-validate="'required|max_value:0'"
              type="number"
            />
            <span class="md-error">
              {{ errors.first($tc("phrases.riskBalance")) }}
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
            {{ $tc("words.save") }}
          </md-button>
        </md-card-actions>
      </md-card>
    </form>
  </widget>
</template>
<script>
import { EventBus } from "@/shared/eventbus"
import { AgentCommissionService } from "@/services/AgentCommissionService"
import Widget from "@/shared/Widget.vue"
import { notify } from "@/mixins/notify"

export default {
  name: "NewCommission",
  mixins: [notify],
  data() {
    return {
      agentCommissionService: new AgentCommissionService(),
      loading: false,
    }
  },
  components: { Widget },
  props: {
    addNewCommission: {
      type: Boolean,
      default: false,
    },
  },
  methods: {
    async saveCommission() {
      let validator = await this.$validator.validateAll()

      if (validator) {
        this.loading = true
        try {
          await this.agentCommissionService.createAgentCommission()
          this.loading = false
          this.commissionAdded()
          this.alertNotify("success", this.$tc("phrases.addCommissionType", 2))
        } catch (e) {
          this.loading = false
          this.alertNotify("error", e.message)
        }
      }
    },
    hide() {
      EventBus.$emit("newCommissionClosed")
    },
    commissionAdded() {
      EventBus.$emit("commissionAdded")
    },
  },
}
</script>
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
