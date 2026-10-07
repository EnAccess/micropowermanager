<template>
  <md-dialog
    class="new-ticket-dialog"
    :md-active="active"
    @update:mdActive="onActiveChange"
  >
    <md-dialog-title>{{ $tc("phrases.newTicket") }}</md-dialog-title>
    <md-dialog-content class="md-scrollbar">
      <form class="md-layout md-gutter">
        <div v-if="ownerId === null" class="md-layout-item md-size-100">
          <customer-search-select
            v-model="selectedCustomerId"
            v-validate="'required'"
            :data-vv-name="$tc('words.customer')"
            :error="errors.first($tc('words.customer'))"
          />
        </div>

        <div class="md-layout-item md-size-100">
          <md-field :class="{ 'md-invalid': errors.has($tc('words.title')) }">
            <label for="title">{{ $tc("words.title") }}</label>
            <md-input
              type="text"
              v-model="ticket.title"
              id="title"
              :name="$tc('words.title')"
              v-validate="'required|min:3'"
            />
            <span class="md-error">
              {{ errors.first($tc("words.title")) }}
            </span>
          </md-field>
        </div>

        <div class="md-layout-item md-size-100" style="display: inline-flex">
          <md-datepicker
            name="ticketDueDate"
            md-immediately
            v-model="ticket.dueDate"
            :md-close-on-blur="false"
          >
            <label for="ticketDueDate">{{ $tc("phrases.dueDate") }}</label>
          </md-datepicker>
        </div>

        <div class="md-layout-item md-size-100">
          <md-field
            :class="{ 'md-invalid': errors.has($tc('words.category')) }"
          >
            <label for="ticketCategory">{{ $tc("words.category") }}</label>
            <md-select
              v-model="ticket.label"
              :name="$tc('words.category')"
              id="ticketCategory"
              v-validate="'required'"
            >
              <md-option
                v-for="label in labels"
                :value="label.id"
                :key="label.id"
              >
                {{ label.label_name }}
              </md-option>
            </md-select>
            <span class="md-error">
              {{ errors.first($tc("words.category")) }}
            </span>
          </md-field>
        </div>

        <div class="md-layout-item md-size-100">
          <md-field>
            <label for="ticketAssignedTo">
              {{ $tc("phrases.assignTo", 0) }}
            </label>
            <md-select
              name="ticketAssignedTo"
              id="ticketAssignedTo"
              v-model="ticket.assignedPerson"
            >
              <md-option v-for="user in users" :value="user.id" :key="user.id">
                {{ user.name }}
              </md-option>
            </md-select>
          </md-field>
        </div>

        <div class="md-layout-item md-size-100">
          <md-field
            :class="{ 'md-invalid': errors.has($tc('words.description')) }"
          >
            <label for="description">{{ $tc("words.description") }}</label>
            <md-textarea
              id="description"
              :name="$tc('words.description')"
              v-model="ticket.description"
              v-validate="'required|min:3'"
            />
            <span class="md-error">
              {{ errors.first($tc("words.description")) }}
            </span>
          </md-field>
        </div>
      </form>
    </md-dialog-content>
    <md-dialog-actions>
      <md-button class="md-accent" @click="close">
        {{ $tc("words.close") }}
      </md-button>
      <md-button class="md-primary" :disabled="saving" @click="save">
        {{ $tc("words.save") }}
      </md-button>
    </md-dialog-actions>
  </md-dialog>
</template>

<script>
import moment from "moment"

import { notify } from "@/mixins/notify.js"
import Client from "@/repositories/Client/AxiosClient.js"
import { TicketLabelService } from "@/services/TicketLabelService.js"
import { TicketUserService } from "@/services/TicketUserService.js"
import CustomerSearchSelect from "@/shared/CustomerSearchSelect.vue"

const emptyTicket = () => ({
  title: "",
  description: "",
  dueDate: null,
  label: null,
  assignedPerson: null,
})

export default {
  name: "NewTicketDialog",
  components: { CustomerSearchSelect },
  mixins: [notify],
  props: {
    active: {
      type: Boolean,
      required: true,
    },
    resource: {
      type: String,
      required: true,
    },
    ownerId: {
      default: null,
    },
  },
  data() {
    return {
      ticketLabelService: new TicketLabelService(),
      ticketUserService: new TicketUserService(),
      labels: [],
      users: [],
      ticket: emptyTicket(),
      selectedCustomerId: null,
      saving: false,
    }
  },
  watch: {
    active(visible) {
      if (visible && !this.labels.length) this.loadOptions()
    },
  },
  methods: {
    async loadOptions() {
      try {
        const [labels, users] = await Promise.all([
          this.ticketLabelService.getLabels(),
          this.ticketUserService.getUsers(),
        ])
        this.labels = labels
        this.users = users
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    onActiveChange(visible) {
      if (!visible) this.close()
    },
    close() {
      this.ticket = emptyTicket()
      this.selectedCustomerId = null
      this.$validator.reset()
      this.$emit("close")
    },
    async save() {
      if (!(await this.$validator.validateAll())) return

      this.saving = true
      try {
        await Client.post(this.resource, {
          ...this.ticket,
          owner_id: this.ownerId ?? this.selectedCustomerId,
          dueDate: this.ticket.dueDate
            ? moment(this.ticket.dueDate).format("YYYY-MM-DD HH:mm:ss")
            : null,
        })
        this.alertNotify("success", "Ticket created successfully.")
        this.$emit("created")
        this.close()
      } catch (e) {
        this.alertNotify("error", e.response?.data?.message ?? e.message)
      } finally {
        this.saving = false
      }
    },
  },
}
</script>

<style scoped lang="scss">
.new-ticket-dialog ::v-deep .md-dialog-container {
  width: 50rem;
  max-width: 90vw;
}
</style>
