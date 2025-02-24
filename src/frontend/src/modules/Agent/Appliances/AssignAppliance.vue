<template>
  <md-dialog :md-active.sync="assignNewAppliance">
    <!-- assing new appliance -->
    <form novalidate class="md-layout" @submit.prevent="saveAppliance">
      <md-card class="md-layout-item">
        <md-card-header>
          <div style="float: right; cursor: pointer" @click="hide()">
            <md-icon>close</md-icon>
            &nbsp;{{ $tc("words.close") }}
          </div>
        </md-card-header>
        <md-card-content>
          <md-field
            :class="{
              'md-invalid': errors.has($tc('words.appliance')),
            }"
          >
            <label>{{ $tc("words.appliance") }}</label>
            <md-select
              :name="$tc('words.appliance')"
              id="applianceTypes"
              v-model="applianceIndex"
              v-validate="'required'"
            >
              <md-option disabled value>
                &#45;&#45;{{ $tc("words.select") }}&#45;&#45;
              </md-option>
              <md-option
                v-for="applianceType in applianceService.list"
                :value="applianceType.id"
                :key="applianceType.id"
              >
                {{ applianceType.name }}
              </md-option>
            </md-select>
            <span class="md-error">
              {{ errors.first($tc("words.appliance")) }}
            </span>
          </md-field>

          <md-field :class="{ 'md-invalid': errors.has($tc('words.cost')) }">
            <label for="cost">{{ $tc("words.cost") }}</label>
            <md-input
              type="number"
              :name="$tc('words.cost')"
              id="cost"
              v-model="newAppliance.cost"
              disabled
              readonly
              v-validate="'required'"
            />
            <span class="md-error">
              {{ errors.first($tc("words.cost")) }}
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
            {{ $tc("phrases.assignAppliance", 0) }}
          </md-button>
        </md-card-actions>
      </md-card>
    </form>
  </md-dialog>
</template>
<script>
import { AgentAssignedApplianceService } from "@/services/AgentAssignedApplianceService"
import { EventBus } from "@/shared/eventbus"
import { ApplianceService } from "@/services/ApplianceService"
import { notify } from "@/mixins/notify"

export default {
  name: "AssignAppliance",
  mixins: [notify],
  data() {
    return {
      applianceIndex: null,
      assignedApplianceService: new AgentAssignedApplianceService(),
      applianceService: new ApplianceService(),
      loading: false,
      agent: {},
      newAppliance: {
        id: null,
        name: null,
        cost: null,
      },
    }
  },
  props: {
    agentId: {
      default: null,
    },
    assignNewAppliance: {
      type: Boolean,
      default: false,
    },
  },
  mounted() {
    this.getAppliances()
  },
  watch: {
    applianceIndex() {
      const appliance = this.applianceService.list.find(
        (x) => x.id === this.applianceIndex,
      )
      this.newAppliance.id = appliance.id
      this.newAppliance.cost = appliance.price
      this.newAppliance.name = appliance.name
    },
  },
  methods: {
    async getAppliances() {
      try {
        await this.applianceService.getAppliances()
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async saveAppliance() {
      let validator = await this.$validator.validateAll()

      if (validator) {
        this.loading = true
        try {
          let userId =
            this.$store.getters["auth/authenticationService"].authenticateUser
              .id
          await this.assignedApplianceService.assignAppliance(
            this.newAppliance,
            userId,
            this.agentId,
          )
          this.loading = false
          this.applianceAssigned()
          this.alertNotify("success", this.$tc("phrases.assignAppliance", 3))
        } catch (e) {
          this.loading = false
          this.alertNotify("error", e.message)
        }
      }
    },
    hide() {
      EventBus.$emit("assignApplianceClosed")
    },
    applianceAssigned() {
      EventBus.$emit("applianceAssigned")
    },
  },
}
</script>
<style scoped></style>
