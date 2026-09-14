<template>
  <div class="col-sm-12">
    <widget
      :subscriber="subscriber"
      color="primary"
      :title="$tc('phrases.userTicket', 2)"
      :paginator="tickets.paginator"
      :button="true"
      :button-text="$tc('phrases.newTicket')"
      @widgetAction="openModal"
      :resetKey="resetKey"
    >
      <ticket-item
        :allow-lock="false"
        :show-client="false"
        :ticket-list="tickets.list"
      ></ticket-item>
    </widget>

    <md-dialog :md-active.sync="showModal">
      <md-dialog-title>{{ $tc("phrases.newTicket") }}</md-dialog-title>
      <md-dialog-content class="md-scrollbar">
        <form class="md-layout md-gutter">
          <div class="md-layout-item md-size-100">
            <md-field
              :class="{
                'md-invalid': errors.has($tc('words.title')),
              }"
            >
              <label for="title">{{ $tc("words.title") }}</label>
              <md-input
                type="text"
                v-model="newTicket.title"
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
              v-model="newTicket.dueDate"
              :md-close-on-blur="false"
            >
              <label for="ticketDueDate">
                {{ $tc("phrases.dueDate") }}
              </label>
            </md-datepicker>
          </div>
          <div class="md-layout-item md-size-100">
            <md-field
              :class="{
                'md-invalid': errors.has($tc('words.category')),
              }"
            >
              <label for="ticketPriority">
                {{ $tc("words.category") }}
              </label>
              <md-select
                v-model="newTicket.label"
                :name="$tc('words.category')"
                id="ticketPriority"
                v-validate="'required'"
              >
                <md-option value="0" disabled>
                  -- {{ $tc("words.select") }} --
                </md-option>
                <md-option
                  v-for="(label, index) in labels"
                  :value="label.id"
                  :key="index"
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
            <md-field name="ticketAssignedTo">
              <label for="ticketAssignedTo">
                {{ $tc("phrases.assignTo", 0) }}
              </label>
              <md-select
                name="ticketAssignedTo"
                id="ticketAssignedTo"
                v-model="newTicket.assignedPerson"
              >
                <md-option disabled selected>
                  {{ $tc("phrases.noOne") }}
                </md-option>
                <md-option
                  v-for="user in users"
                  :value="user.id"
                  :key="user.id"
                >
                  {{ user.name }}
                </md-option>
              </md-select>
            </md-field>
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
                type="text"
                id="description"
                :name="$tc('words.description')"
                v-model="newTicket.description"
                v-validate="'required|min:3'"
              />
              <span class="md-error">
                {{ errors.first($tc("words.description")) }}
              </span>
            </md-field>
          </div>
          <md-dialog-actions class="md-layout-item md-size-100">
            <md-button class="md-accent" @click="closeModal()">
              {{ $tc("words.close") }}
            </md-button>

            <md-button class="md-primary btn-lg" @click="saveTicket()">
              {{ $tc("words.save") }}
            </md-button>
          </md-dialog-actions>
        </form>
      </md-dialog-content>
    </md-dialog>
  </div>
</template>

<script>
import moment from "moment"

import { notify } from "@/mixins/notify.js"
import Client from "@/repositories/Client/AxiosClient.js"
import { resources } from "@/resources.js"
import { TicketLabelService } from "@/services/TicketLabelService.js"
import { TicketList } from "@/services/TicketService.js"
import { TicketUserService } from "@/services/TicketUserService.js"
import { EventBus } from "@/shared/eventbus.js"
import TicketItem from "@/shared/TicketItem.vue"
import Widget from "@/shared/Widget.vue"

export default {
  name: "Ticket",
  components: { TicketItem, Widget },
  mixins: [notify],
  props: {
    personId: {
      required: true,
    },
  },
  data() {
    return {
      ticketLabelService: new TicketLabelService(),
      ticketUserService: new TicketUserService(),
      subscriber: "userTickets",
      tickets: new TicketList(resources.ticket.getUser + this.personId),
      showPriceInput: false,
      showModal: false,
      users: {},
      labels: [],
      newTicket: {
        title: "",
        description: "",
        dueDate: null,
        label: null,
        assignedPerson: null,
        owner_id: this.personId,
        owner_type: "person",
        creator:
          this.$store.getters["auth/authenticationService"].authenticateUser.id,
        outsourcing: 0,
      },
      resetKey: 0,
    }
  },
  beforeDestroy() {
    EventBus.$off("pageLoaded", this.reloadList)
  },

  mounted() {
    EventBus.$on("pageLoaded", this.reloadList)
    this.getUsers()
    this.getLabels()
    this.$on("close", function () {
      this.showModal = false
    })
  },
  methods: {
    ticketCategoryChange(label) {
      // is needed for outsourcing.

      let category = this.labels.filter((l) => {
        return l.id == label.target.value
      })

      if (category.length === 0) {
        return
      }

      category = category[0]

      if (category.out_source === 1) {
        this.showPriceInput = true
        this.newTicket.outsourcing = 1
      }
    },
    reloadList(sub, data) {
      if (sub !== this.subscriber) return
      this.tickets.updateList(data)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.tickets.list.length,
      )
    },
    closeModal() {
      this.showModal = false
      this.resetForm()
      this.$validator.reset()
    },
    resetForm() {
      this.newTicket = {
        title: "",
        description: "",
        dueDate: null,
        label: null,
        assignedPerson: null,
        owner_id: this.personId,
        owner_type: "person",
        creator:
          this.$store.getters["auth/authenticationService"].authenticateUser.id,
        outsourcing: 0,
      }
      this.showPriceInput = false
    },
    openModal() {
      this.showModal = true
    },
    async getUsers() {
      this.users = await this.ticketUserService.getUsers()
    },
    async getLabels() {
      this.labels = await this.ticketLabelService.getLabels()
    },

    async saveTicket() {
      if (!this.$can("tickets")) {
        this.alertNotify(
          "error",
          "You do not have permission to create tickets",
        )
        return
      }

      // Validate all fields
      const validator = await this.$validator.validateAll()
      if (!validator) {
        return
      }

      //validate ticket
      if (this.showPriceInput && this.newTicket.outsourcing === 0) {
        this.$swal({
          type: "error",
          title: "Value Error!",
          text: 'Please enter the amount in the "Amount" field.',
        })
        return
      }

      const newTicketParams = {
        ...this.newTicket,
        dueDate: this.newTicket.dueDate
          ? moment(this.newTicket.dueDate).format("YYYY-MM-DD HH:mm:ss")
          : null,
      }

      try {
        await Client.post(resources.ticket.create, newTicketParams)
        this.alertNotify("success", "Ticket created successfully.")
        // Refresh ticket list
        EventBus.$emit(
          "widgetContentLoaded",
          this.subscriber,
          this.tickets.list.length,
        )
        this.resetKey++
        // Reset form and close modal
        this.resetForm()
        this.closeModal()
      } catch (error) {
        console.error("Error creating ticket:", error)
        if (error.response && error.response.status === 403) {
          this.alertNotify(
            "error",
            "You do not have permission to create tickets",
          )
        } else {
          this.alertNotify("error", error.message)
        }
      }
    },
  },
}
</script>

<style scoped lang="scss"></style>
