<template>
  <div v-if="smsApplianceRemindRateService.list.length">
    <form
      @submit.prevent="saveSmsApplianceRemindRate"
      class="md-layout md-gutter"
      data-vv-scope="Remind-Rate-Form"
    >
      <div
        class="md-layout-item md-xlarge-size-25 md-large-size-25 md-medium-size-25 md-small-size-25"
      >
        <md-field
          :class="{
            'md-invalid': errors.has(
              'Remind-Rate-Form.' + $tc('words.appliance'),
            ),
          }"
        >
          <label for="name">{{ $tc("words.appliance") }}</label>
          <md-select
            v-model="selectedRemindRateId"
            @md-selected="smsApplianceRemindRateSelected"
            name="remindRate"
            id="remindRate"
          >
            <md-option
              v-for="(remindRate, index) in smsApplianceRemindRateService.list"
              :value="remindRate.id"
              :key="index"
            >
              {{ remindRate.applianceType }}
            </md-option>
          </md-select>
          <span class="md-error">
            {{ errors.first("Remind-Rate-Form." + $tc("words.appliance")) }}
          </span>
        </md-field>
      </div>
      <div
        class="md-layout-item md-xlarge-size-20 md-large-size-20 md-medium-size-20 md-small-size-20"
      >
        <md-field
          :class="{
            'md-invalid': errors.has(
              'Remind-Rate-Form.' + $tc('phrases.overDueReminderRate'),
            ),
          }"
        >
          <label for="overDueReminderRate">
            {{ $tc("phrases.overDueReminderRate") }}
          </label>
          <md-input
            id="overDueReminderRate"
            :name="$tc('phrases.overDueReminderRate')"
            v-model="
              smsApplianceRemindRateService.smsApplianceRemindRate
                .overdueRemindRate
            "
            v-validate="'required|integer'"
          />
          <span class="md-error">
            {{
              errors.first(
                "Remind-Rate-Form." + $tc("phrases.overDueReminderRate"),
              )
            }}
          </span>
        </md-field>
      </div>
      <div
        class="md-layout-item md-xlarge-size-20 md-large-size-20 md-medium-size-20 md-small-size-20"
      >
        <md-field
          :class="{
            'md-invalid': errors.has(
              'Remind-Rate-Form.' + $tc('phrases.reminderRate'),
            ),
          }"
        >
          <label for="price">{{ $tc("phrases.reminderRate") }}</label>
          <md-input
            id="price"
            :name="$tc('phrases.reminderRate')"
            v-model="
              smsApplianceRemindRateService.smsApplianceRemindRate.remindRate
            "
            v-validate="'required|integer'"
          />
          <span class="md-error">
            {{
              errors.first("Remind-Rate-Form." + $tc("phrases.reminderRate"))
            }}
          </span>
        </md-field>
      </div>
      <div
        class="md-layout-item md-xlarge-size-20 md-large-size-20 md-medium-size-20 md-small-size-20"
      >
        <md-switch
          v-model="smsApplianceRemindRateService.smsApplianceRemindRate.enabled"
          class="md-primary"
        >
          {{ $tc("phrases.enableSmsReminder") }}
        </md-switch>
      </div>
      <div
        class="md-layout-item md-xlarge-size-15 md-large-size-15 md-medium-size-15 md-small-size-15"
      >
        <md-button
          role="button"
          type="submit"
          class="md-raised md-primary"
          :disabled="loading"
        >
          {{ $tc("words.save") }}
        </md-button>
      </div>
    </form>
    <md-progress-bar v-if="loading" md-mode="indeterminate"></md-progress-bar>
    <md-table v-if="savedRemindRates.length" class="remind-rate-table">
      <md-table-row>
        <md-table-head>{{ $tc("words.appliance") }}</md-table-head>
        <md-table-head>{{ $tc("phrases.reminderRate") }}</md-table-head>
        <md-table-head>{{ $tc("phrases.overDueReminderRate") }}</md-table-head>
        <md-table-head>{{ $tc("phrases.enableSmsReminder") }}</md-table-head>
      </md-table-row>
      <md-table-row
        v-for="(rate, index) in savedRemindRates"
        :key="index"
        @click="smsApplianceRemindRateSelected(rate.id)"
        class="clickable-row"
      >
        <md-table-cell>{{ rate.applianceType }}</md-table-cell>
        <md-table-cell>
          {{ rate.remindRate }} {{ $tc("words.day") }}
        </md-table-cell>
        <md-table-cell>
          {{ rate.overdueRemindRate }} {{ $tc("words.day") }}
        </md-table-cell>
        <md-table-cell>
          <md-icon :class="rate.enabled ? 'enabled-icon' : 'disabled-icon'">
            {{ rate.enabled ? "check_circle" : "cancel" }}
          </md-icon>
        </md-table-cell>
      </md-table-row>
    </md-table>
  </div>
</template>
<script>
import { SmsApplianceRemindRateService } from "@/services/SmsApplianceRemindRateService"
import { notify } from "@/mixins/notify"

export default {
  name: "SmsApplianceRemindRate",
  mixins: [notify],
  data() {
    return {
      smsApplianceRemindRateService: new SmsApplianceRemindRateService(),
      loading: false,
      selectedRemindRateId: 0,
    }
  },
  computed: {
    savedRemindRates() {
      return this.smsApplianceRemindRateService.list.filter(
        (rate) => rate.id > 0,
      )
    },
  },
  mounted() {
    this.getSmsApplianceRemindRate()
  },
  methods: {
    async getSmsApplianceRemindRate() {
      try {
        await this.smsApplianceRemindRateService.getSmsApplianceRemindRates()
        this.selectedRemindRateId =
          this.smsApplianceRemindRateService.smsApplianceRemindRate.id
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async saveSmsApplianceRemindRate() {
      try {
        this.loading = true
        await this.smsApplianceRemindRateService.updateSmsApplianceRemindRate()
        await this.smsApplianceRemindRateService.getSmsApplianceRemindRates()
        this.alertNotify("success", "Updated Successfully")
      } catch (e) {
        this.alertNotify("error", e.message)
      }
      this.loading = false
    },
    smsApplianceRemindRateSelected(smsApplianceRemindRate) {
      this.selectedRemindRateId = smsApplianceRemindRate
      this.smsApplianceRemindRateService.smsApplianceRemindRate =
        this.smsApplianceRemindRateService.list.filter(
          (x) => x.id === smsApplianceRemindRate,
        )[0]
    },
  },
}
</script>

<style scoped>
.remind-rate-table {
  margin-top: 1rem;
}
.clickable-row {
  cursor: pointer;
}
.clickable-row:hover {
  background-color: #f5f5f5;
}
.enabled-icon {
  color: #4caf50 !important;
}
.disabled-icon {
  color: #f44336 !important;
}
</style>
