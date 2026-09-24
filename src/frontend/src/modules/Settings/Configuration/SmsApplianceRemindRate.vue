<template>
  <div v-if="smsApplianceRemindRateService.list.length">
    <widget :hidden="!showAddForm" title="Add Reminder" color="secondary">
      <md-card>
        <md-card-content>
          <form
            @submit.prevent="addSmsApplianceRemindRate"
            class="md-layout md-gutter"
            data-vv-scope="Remind-Rate-Form"
          >
            <div class="md-layout-item md-size-100 md-small-size-100">
              <md-field
                :class="{
                  'md-invalid': errors.has(
                    'Remind-Rate-Form.' + $tc('words.appliance'),
                  ),
                }"
              >
                <label>{{ $tc("words.appliance") }}</label>
                <md-select
                  v-model="selectedRemindRateId"
                  @md-selected="smsApplianceRemindRateSelected"
                  name="remindRate"
                  id="remindRate"
                >
                  <md-option
                    v-for="(remindRate, index) in unconfiguredApplianceTypes"
                    :value="remindRate.id"
                    :key="index"
                  >
                    {{ remindRate.applianceType }}
                  </md-option>
                </md-select>
                <span class="md-error">
                  {{
                    errors.first("Remind-Rate-Form." + $tc("words.appliance"))
                  }}
                </span>
              </md-field>
            </div>
            <div class="md-layout-item md-size-50 md-small-size-100">
              <md-switch
                v-model="
                  smsApplianceRemindRateService.smsApplianceRemindRate
                    .upcomingReminderEnabled
                "
                class="md-primary"
              >
                {{ $tc("phrases.upcomingReminder") }}
              </md-switch>
              <div class="reminder-days-field">
                <md-field
                  :class="{
                    'md-invalid': errors.has(
                      'Remind-Rate-Form.' + $tc('phrases.daysBeforeDueDate'),
                    ),
                  }"
                >
                  <label>{{ $tc("phrases.daysBeforeDueDate") }}</label>
                  <md-input
                    type="number"
                    min="0"
                    :name="$tc('phrases.daysBeforeDueDate')"
                    :disabled="
                      !smsApplianceRemindRateService.smsApplianceRemindRate
                        .upcomingReminderEnabled
                    "
                    v-model="
                      smsApplianceRemindRateService.smsApplianceRemindRate
                        .remindRate
                    "
                    v-validate="'required|integer|min_value:0'"
                  />
                  <span class="md-error">
                    {{
                      errors.first(
                        "Remind-Rate-Form." + $tc("phrases.daysBeforeDueDate"),
                      )
                    }}
                  </span>
                </md-field>
                <md-icon class="reminder-help">
                  help_outline
                  <md-tooltip md-direction="top">
                    {{ $tc("phrases.upcomingReminderHelp") }}
                  </md-tooltip>
                </md-icon>
              </div>
            </div>
            <div class="md-layout-item md-size-50 md-small-size-100">
              <md-switch
                v-model="
                  smsApplianceRemindRateService.smsApplianceRemindRate
                    .overdueReminderEnabled
                "
                class="md-primary"
              >
                {{ $tc("phrases.overdueReminder") }}
              </md-switch>
              <div class="reminder-days-field">
                <md-field
                  :class="{
                    'md-invalid': errors.has(
                      'Remind-Rate-Form.' + $tc('phrases.daysAfterDueDate'),
                    ),
                  }"
                >
                  <label>{{ $tc("phrases.daysAfterDueDate") }}</label>
                  <md-input
                    type="number"
                    min="1"
                    :name="$tc('phrases.daysAfterDueDate')"
                    :disabled="
                      !smsApplianceRemindRateService.smsApplianceRemindRate
                        .overdueReminderEnabled
                    "
                    v-model="
                      smsApplianceRemindRateService.smsApplianceRemindRate
                        .overdueRemindRate
                    "
                    v-validate="'required|integer|min_value:1'"
                  />
                  <span class="md-error">
                    {{
                      errors.first(
                        "Remind-Rate-Form." + $tc("phrases.daysAfterDueDate"),
                      )
                    }}
                  </span>
                </md-field>
                <md-icon class="reminder-help">
                  help_outline
                  <md-tooltip md-direction="top">
                    {{ $tc("phrases.overdueReminderHelp") }}
                  </md-tooltip>
                </md-icon>
              </div>
            </div>
            <div class="md-layout-item md-size-100 md-small-size-100">
              <md-switch
                v-model="
                  smsApplianceRemindRateService.smsApplianceRemindRate
                    .createTicket
                "
                class="md-primary"
              >
                {{ $tc("phrases.createTicket") }}
              </md-switch>
              <md-icon class="reminder-help">
                help_outline
                <md-tooltip md-direction="top">
                  {{ $tc("phrases.createTicketHelp") }}
                </md-tooltip>
              </md-icon>
            </div>
          </form>
          <md-progress-bar
            v-if="loading"
            md-mode="indeterminate"
          ></md-progress-bar>
        </md-card-content>
        <md-card-actions>
          <md-button
            class="md-raised md-primary"
            @click="addSmsApplianceRemindRate"
            :disabled="loading"
          >
            {{ $tc("words.save") }}
          </md-button>
          <md-button class="md-raised" @click="showAddForm = false">
            {{ $tc("words.close") }}
          </md-button>
        </md-card-actions>
      </md-card>
    </widget>
    <widget
      title="Appliance Reminder Rates"
      :button="true"
      :button-text="$tc('phrases.newAppliance', 1)"
      @widgetAction="showAddForm = true"
    >
      <p class="reminder-description">
        {{ $tc("phrases.smsApplianceReminderDescription") }}
      </p>
      <div class="table-scroll-wrapper">
        <md-table v-if="savedRemindRates.length">
          <md-table-row>
            <md-table-head>{{ $tc("words.appliance") }}</md-table-head>
            <md-table-head>
              {{ $tc("phrases.upcomingReminder") }}
              <md-icon class="reminder-help">
                help_outline
                <md-tooltip md-direction="top">
                  {{ $tc("phrases.upcomingReminderHelp") }}
                </md-tooltip>
              </md-icon>
            </md-table-head>
            <md-table-head>
              {{ $tc("phrases.overdueReminder") }}
              <md-icon class="reminder-help">
                help_outline
                <md-tooltip md-direction="top">
                  {{ $tc("phrases.overdueReminderHelp") }}
                </md-tooltip>
              </md-icon>
            </md-table-head>
            <md-table-head>
              {{ $tc("phrases.createTicket") }}
              <md-icon class="reminder-help">
                help_outline
                <md-tooltip md-direction="top">
                  {{ $tc("phrases.createTicketHelp") }}
                </md-tooltip>
              </md-icon>
            </md-table-head>
            <md-table-head></md-table-head>
          </md-table-row>
          <md-table-row v-for="(rate, index) in savedRemindRates" :key="index">
            <md-table-cell>{{ rate.applianceType }}</md-table-cell>
            <md-table-cell>
              <div v-if="editingIndex === index" class="reminder-edit-cell">
                <md-switch
                  v-model="rate.upcomingReminderEnabled"
                  class="md-primary"
                />
                <md-field>
                  <label>{{ $tc("phrases.daysBeforeDueDate") }}</label>
                  <md-input
                    type="number"
                    min="0"
                    :disabled="!rate.upcomingReminderEnabled"
                    v-model="rate.remindRate"
                  ></md-input>
                </md-field>
              </div>
              <span v-else-if="rate.upcomingReminderEnabled">
                {{ rate.remindRate }} {{ $tc("phrases.daysBeforeDueDate") }}
              </span>
              <md-icon v-else class="md-accent">close</md-icon>
            </md-table-cell>
            <md-table-cell>
              <div v-if="editingIndex === index" class="reminder-edit-cell">
                <md-switch
                  v-model="rate.overdueReminderEnabled"
                  class="md-primary"
                />
                <md-field>
                  <label>{{ $tc("phrases.daysAfterDueDate") }}</label>
                  <md-input
                    type="number"
                    min="1"
                    :disabled="!rate.overdueReminderEnabled"
                    v-model="rate.overdueRemindRate"
                  ></md-input>
                </md-field>
              </div>
              <span v-else-if="rate.overdueReminderEnabled">
                {{ rate.overdueRemindRate }}
                {{ $tc("phrases.daysAfterDueDate") }}
              </span>
              <md-icon v-else class="md-accent">close</md-icon>
            </md-table-cell>
            <md-table-cell>
              <md-switch
                v-if="editingIndex === index"
                v-model="rate.createTicket"
                class="md-primary"
              />
              <md-icon
                v-else
                :class="rate.createTicket ? 'md-primary' : 'md-accent'"
              >
                {{ rate.createTicket ? "check" : "close" }}
              </md-icon>
            </md-table-cell>
            <md-table-cell>
              <div v-if="editingIndex === index" class="md-layout md-gutter">
                <md-button
                  class="md-primary md-dense"
                  @click="updateRemindRate(rate)"
                >
                  <md-icon class="md-primary">save</md-icon>
                  {{ $tc("words.save") }}
                </md-button>
                <md-button class="md-accent md-dense" @click="cancelEdit">
                  <md-icon class="md-accent">close</md-icon>
                  {{ $tc("words.close") }}
                </md-button>
              </div>
              <div v-else class="md-layout md-gutter">
                <md-button
                  class="md-primary md-dense"
                  @click="startEdit(index)"
                >
                  <md-icon>edit</md-icon>
                  {{ $tc("words.edit") }}
                </md-button>
                <md-button
                  class="md-accent md-dense"
                  :disabled="loading"
                  @click="deleteRemindRate(rate)"
                >
                  <md-icon class="md-accent">delete</md-icon>
                  {{ $tc("words.delete") }}
                </md-button>
              </div>
              <md-progress-bar md-mode="indeterminate" v-if="loading" />
            </md-table-cell>
          </md-table-row>
        </md-table>
      </div>
    </widget>
  </div>
</template>
<script>
import { notify } from "@/mixins/notify.js"
import { SmsApplianceRemindRateService } from "@/services/SmsApplianceRemindRateService.js"
import Widget from "@/shared/Widget.vue"

export default {
  name: "SmsApplianceRemindRate",
  mixins: [notify],
  components: { Widget },
  data() {
    return {
      smsApplianceRemindRateService: new SmsApplianceRemindRateService(),
      loading: false,
      selectedRemindRateId: 0,
      editingIndex: null,
      showAddForm: false,
    }
  },
  computed: {
    savedRemindRates() {
      return this.smsApplianceRemindRateService.list.filter(
        (rate) => rate.id > 0,
      )
    },
    unconfiguredApplianceTypes() {
      return this.smsApplianceRemindRateService.list.filter(
        (rate) => rate.id < 0,
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
        this.selectFirstUnconfigured()
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    selectFirstUnconfigured() {
      if (this.unconfiguredApplianceTypes.length) {
        this.selectedRemindRateId = this.unconfiguredApplianceTypes[0].id
        this.smsApplianceRemindRateService.smsApplianceRemindRate =
          this.unconfiguredApplianceTypes[0]
      }
    },
    async addSmsApplianceRemindRate() {
      const isValid = await this.$validator.validateAll("Remind-Rate-Form")
      if (!isValid) {
        return
      }
      try {
        this.loading = true
        await this.smsApplianceRemindRateService.updateSmsApplianceRemindRate()
        await this.smsApplianceRemindRateService.getSmsApplianceRemindRates()
        this.selectFirstUnconfigured()
        this.showAddForm = false
        this.alertNotify("success", "Added Successfully")
      } catch (e) {
        this.alertNotify("error", e.message)
      }
      this.loading = false
    },
    async updateRemindRate(rate) {
      try {
        this.loading = true
        this.smsApplianceRemindRateService.smsApplianceRemindRate = rate
        await this.smsApplianceRemindRateService.updateSmsApplianceRemindRate()
        await this.smsApplianceRemindRateService.getSmsApplianceRemindRates()
        this.editingIndex = null
        this.alertNotify("success", "Updated Successfully")
      } catch (e) {
        this.alertNotify("error", e.message)
      }
      this.loading = false
    },
    async deleteRemindRate(rate) {
      this.$swal({
        type: "question",
        title: "Delete Reminder",
        text: "Are you sure you want to delete this reminder configuration?",
        showCancelButton: true,
        cancelButtonText: this.$tc("words.cancel"),
        confirmButtonText: this.$tc("words.delete"),
      }).then(async (response) => {
        if (response.value) {
          try {
            this.loading = true
            await this.smsApplianceRemindRateService.deleteSmsApplianceRemindRate(
              rate.id,
            )
            this.selectFirstUnconfigured()
            this.alertNotify("success", "Deleted Successfully")
          } catch (e) {
            this.alertNotify("error", e.message)
          }
          this.loading = false
        }
      })
    },
    startEdit(index) {
      this.editingIndex = index
    },
    cancelEdit() {
      this.editingIndex = null
    },
    smsApplianceRemindRateSelected(id) {
      this.selectedRemindRateId = id
      this.smsApplianceRemindRateService.smsApplianceRemindRate =
        this.smsApplianceRemindRateService.list.find((x) => x.id === id)
    },
  },
}
</script>

<style scoped lang="scss">
.table-scroll-wrapper {
  width: 100%;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.reminder-description {
  margin: 0 16px 8px;
  color: #666;
}

.reminder-days-field,
.reminder-edit-cell {
  display: flex;
  align-items: center;
  gap: 8px;
}

.reminder-help.md-icon {
  margin-left: 4px;
  cursor: help;
  color: #999;
  font-size: 18px;
}
</style>
