<template>
  <div class="col-sm-12">
    <widget
      :subscriber="subscriber"
      color="green"
      :title="$tc('phrases.userTicket', 2)"
      :paginator="tickets.paginator"
      :button="true"
      :button-text="$tc('phrases.newTicket')"
      @widgetAction="openModal"
      :resetKey="resetKey"
    >
      <ticket-item
        :allow-lock="false"
        :allow-comment="true"
        :ticket-list="tickets.list"
        :table-heads="tableHeads"
      ></ticket-item>
    </widget>

    <md-dialog :md-active.sync="showModal">
      <md-dialog-title>{{ $tc("phrases.newTicket") }}</md-dialog-title>
      <md-dialog-content class="md-scrollbar">
        <form novalidate class="md-layout md-gutter">
          <div class="md-layout-item md-size-100">
            <md-field name="title">
              <label for="title">{{ $tc("words.title") }}</label>
              <md-input
                type="text"
                v-model="newTicket.title"
                id="title"
                name="title"
              />
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
            <md-field name="ticketPriority">
              <label for="ticketPriority">
                {{ $tc("words.category") }}
              </label>
              <md-select
                v-model="newTicket.label"
                name="ticketPriority"
                id="ticketPriority"
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
            <md-field>
              <label for="description">
                {{ $tc("words.description") }}
              </label>
              <md-textarea
                type="text"
                id="description"
                name="description"
                v-model="newTicket.description"
              />
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
import Widget from "@/shared/Widget.vue"
import { Ticket, UserTickets } from "@/services/TicketService"
import { resources } from "@/resources"
import { EventBus } from "@/shared/eventbus"
import { Paginator } from "@/Helpers/Paginator"
import moment from "moment"
import { TicketUserService } from "@/services/TicketUserService"
import { TicketLabelService } from "@/services/TicketLabelService"
import TicketItem from "../../shared/TicketItem"
import { baseUrl } from "@/repositories/Client/AxiosClient"

export default {
  name: "Ticket",
  components: { TicketItem, Widget },
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
      tickets: new UserTickets(this.personId),
      showPriceInput: false,
      paginator: null,
      tableHeads: [
        this.$tc("words.subject"),
        this.$tc("words.category"),
        this.$tc("words.status"),
        this.$tc("words.date"),
      ],
      // tickets: [],
      currentPage: 0,
      totalPages: 0,
      perPage: 0,
      showTicket: null,
      currentFrom: 0,
      currentTo: 0,
      total: 0,
      loaded: false,
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
    //this.getTickets();
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
    },
    openModal() {
      this.showModal = true
    },
    setToday() {
      let date = new Date()
      let year = date.getUTCFullYear()
      let month =
        date.getUTCMonth() + 1 < 10
          ? "0" + (date.getUTCMonth() + 1)
          : date.getUTCMonth() + 1
      let day =
        date.getUTCDate() < 10 ? "0" + date.getUTCDate() : date.getUTCDate()
      this.newTicket.dueDate = day + "." + month + "." + year
    },
    getTickets(pageNumber = 1) {
      let personId = this.personId
      this.loaded = false

      if (this.paginator === null)
        this.paginator = new Paginator(resources.ticket.getUser + personId)

      this.paginator.loadPage(pageNumber).then((response) => {
        this.loaded = true
        this.tickets = []

        for (let i in response.data) {
          let t = new Ticket()
          let data = response.data[i]

          this.tickets.push(t.fromJson(data))
        }
      })
    },
    closeTicket(ticket) {
      ticket.close()
    },
    fetchTicket() {},
    dateForHumans(date, format = "YYYY-MM-DD HH:mm:ss") {
      return moment(date, format).fromNow()
    },
    async getUsers() {
      this.users = await this.ticketUserService.getUsers()
    },
    async getLabels() {
      this.labels = await this.ticketLabelService.getLabels()
    },

    saveTicket() {
      //validate ticket
      if (this.showPriceInput && this.newTicket.outsourcing === 0) {
        this.$swal({
          type: "error",
          title: "Value Error!",
          text: 'Please enter the amount in the "Amount" field.',
        })
        return
      }

      axios.post(baseUrl + resources.ticket.create, this.newTicket).then(() => {
        EventBus.$emit(
          "widgetContentLoaded",
          this.subscriber,
          this.tickets.list.length,
        )
        this.resetKey++
      })

      this.$emit("close")
    },
  },
}
</script>

<style scoped></style>
