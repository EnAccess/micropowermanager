<template>
  <div>
    <new-user
      :newUser="newUser"
      @stored="getEmployees"
      @closed="
        () => {
          this.newUser = false
        }
      "
    ></new-user>
    <widget
      :title="$tc('phrases.newMaintenanceRequest')"
      :button-text="$tc('phrases.newMaintenanceUser')"
      :button="true"
      @widgetAction="openNewUser"
      color="green"
    >
      <form @submit.prevent="submitMaintainForm">
        <md-card>
          <md-card-content>
            <div class="md-layout md-gutter">
              <div class="md-layout-item md-size-100">
                <md-field
                  :class="{
                    'md-invalid': errors.has($tc('phrases.jobTitle')),
                  }"
                >
                  <label for="title">
                    {{ $tc("phrases.jobTitle") }}
                  </label>
                  <md-input
                    v-model="maintenanceData.title"
                    type="text"
                    class="input-w form-control"
                    id="title"
                    :name="$tc('phrases.jobTitle')"
                    v-validate="'required'"
                    :placeholder="$tc('phrases.jobTitle')"
                  ></md-input>
                  <span class="md-error">
                    {{ errors.first($tc("phrases.jobTitle")) }}
                  </span>
                </md-field>
              </div>
              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field
                  :class="{
                    'md-invalid': errors.has($tc('words.employee')),
                  }"
                >
                  <label for="employee">
                    {{ $tc("phrases.assignTo") }}
                  </label>
                  <md-select
                    id="employee"
                    :name="$tc('words.employee')"
                    v-validate="'required'"
                    v-model="maintenanceData.assigned"
                  >
                    <md-option value="" disabled selected>
                      -- {{ $tc("words.select") }} --
                    </md-option>
                    <template v-for="employee in employees">
                      <md-option
                        :key="employee.id"
                        v-if="employee.person"
                        :value="employee.id"
                      >
                        {{ employee.person.name }}
                        {{ employee.person.surname }}
                      </md-option>
                    </template>
                  </md-select>
                  <span class="md-error">
                    {{ errors.first($tc("words.employee")) }}
                  </span>
                </md-field>
              </div>
              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field
                  :class="{
                    'md-invalid': errors.has($tc('words.category')),
                  }"
                >
                  <label for="category">
                    {{ $tc("words.category") }}
                  </label>
                  <md-select
                    id="category"
                    :name="$tc('words.category')"
                    v-validate="'required'"
                    v-model="maintenanceData.category"
                  >
                    <md-option value="" disabled selected>
                      -- Select --
                    </md-option>
                    <md-option
                      v-for="(category, index) in categories"
                      :key="index"
                      :value="category.id"
                    >
                      {{ category.label_name }}
                    </md-option>
                  </md-select>
                  <span class="md-error">
                    {{ errors.first($tc("words.category")) }}
                  </span>
                </md-field>
              </div>
              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field
                  :class="{
                    'md-invalid': errors.has($tc('words.amount')),
                  }"
                >
                  <label for="amount">
                    {{ $tc("words.amount") }}
                  </label>
                  <span class="md-prefix">$</span>
                  <md-input
                    v-model="maintenanceData.amount"
                    type="text"
                    id="amount"
                    :name="$tc('words.amount')"
                    v-validate="'required'"
                    :placeholder="$tc('words.amount')"
                  ></md-input>
                  <span class="md-error">
                    {{ errors.first($tc("words.amount")) }}
                  </span>
                </md-field>
              </div>
              <div class="md-layout-item md-size-50 md-small-size-100">
                <div>
                  <md-datepicker
                    :class="{
                      'md-invalid': errors.has($tc('phrases.dueDate')),
                    }"
                    :name="$tc('phrases.dueDate')"
                    md-immediately
                    v-model="maintenanceData.dueDate"
                    v-validate="'required'"
                    :md-close-on-blur="false"
                  >
                    <label>
                      {{ $tc("phrases.dueDate") }}
                    </label>
                    <span class="md-error">
                      {{ errors.first($tc("phrases.dueDate")) }}
                    </span>
                  </md-datepicker>
                </div>
              </div>
              <div class="md-layout-item md-size-100">
                <md-field
                  :class="{
                    'md-invalid': errors.has($tc('words.description')),
                  }"
                >
                  <label for="description">
                    {{ $tc("words.description") }}
                  </label>
                  <md-textarea
                    id="description"
                    :name="$tc('words.description')"
                    v-validate="'required'"
                    v-model="maintenanceData.description"
                  ></md-textarea>
                  <span class="md-error">
                    {{ errors.first($tc("words.description")) }}
                  </span>
                </md-field>
              </div>
            </div>
            <!-- end layout -->
            <md-progress-bar md-mode="indeterminate" v-if="loading" />
          </md-card-content>

          <md-card-actions>
            <md-button
              class="md-raised md-primary"
              type="submit"
              :disabled="loading"
            >
              <md-icon>save</md-icon>
              {{ $tc("words.save") }}
            </md-button>
          </md-card-actions>
        </md-card>
      </form>
    </widget>
  </div>
</template>

<script>
import Widget from "@/shared/Widget.vue"
import NewUser from "./NewUser"
import { EventBus } from "@/shared/eventbus"
import { TicketService } from "@/services/TicketService"
import { MaintenanceService } from "@/services/MaintenanceService"
import { SmsService } from "@/services/SmsService"
import { notify } from "@/mixins/notify"

export default {
  name: "Maintenance",
  mixins: [notify],
  components: { NewUser, Widget },
  data() {
    return {
      newUser: false,
      categories: [],
      employees: [],
      maintenanceData: null,
      ticketService: new TicketService(),
      maintenanceService: new MaintenanceService(),
      smsService: new SmsService(),
      selectedDue: null,
      loading: false,
    }
  },
  watch: {
    selectedDue: function (date) {
      this.dueDateSelected(date)
    },
  },
  created() {
    this.maintenanceData = this.maintenanceService.personData
    this.maintenanceData.creator =
      this.$store.getters["auth/authenticationService"].authenticateUser.id
  },
  mounted() {
    this.getCategories()
    this.getEmployees()
    EventBus.$on("newUserClosed", this.newUserClose)
  },
  methods: {
    newUserClose() {
      this.newUser = false
      this.getEmployees()
    },
    async getCategories() {
      try {
        await this.ticketService.getCategories()
        this.categories = this.ticketService.categories.filter(
          (cat) => cat.out_source === 1,
        )
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async getEmployees() {
      try {
        this.employees = await this.maintenanceService.getEmployees()
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    dueDateSelected(date) {
      if (date === null) {
        return
      }
      this.maintenanceService.setDueDate(date)
    },
    async submitMaintainForm() {
      let validator = await this.$validator.validateAll()
      if (validator) {
        await this.saveTicket()
        this.maintenanceData = {
          description: null,
          selectedDue: null,
          amount: null,
          category: null,
          assigned: null,
          title: null,
        }
      }
      this.$validator.reset()
    },
    async saveTicket() {
      try {
        this.loading = true
        await this.ticketService.createMaintenanceTicket(this.maintenanceData)
        await this.smsService.sendMaintenanceSms(this.maintenanceData)
        this.alertNotify(
          "success",
          this.$tc("phrases.newMaintenanceRequest", 2),
        )
        this.maintenanceService.resetMaintenance()
        this.loading = false
      } catch (e) {
        this.alertNotify("error", e.message)
        this.loading = false
      }
    },
    openNewUser() {
      EventBus.$emit("getLists")
      this.newUser = true
    },
  },
}
</script>

<style scoped>
.label-w {
  width: 84vw;
}

.input-w {
  width: 84vw;
}

.dp {
  margin-left: 10vw;
  margin-top: -22px;
}

.dp-input {
  width: 100% !important;
}

.margin {
  margin-top: 7px;
  margin-bottom: 8px;
  content: " ";
  clear: both;
}
</style>
