<template>
  <div
    class="md-layout md-gutter md-size-100"
    style="padding: 0.4rem; margin: auto"
  >
    <div class="md-layout-item md-size-33 md-small-size-100">
      <md-field>
        <md-select
          @md-selected="setCategory"
          id="ticket_categories"
          name="ticket_categories"
          :placeholder="$tc('phrases.anyCategory')"
        >
          <md-option value>-- {{ $tc("phrases.anyCategory") }} --</md-option>
          <md-option
            :key="index"
            :value="category.id"
            v-for="(category, index) in ticketService.categories"
          >
            {{ category.label_name }}
          </md-option>
        </md-select>
      </md-field>
    </div>

    <div class="md-layout-item md-size-33 md-small-size-100">
      <md-field class="md-layout-item">
        <md-select
          @md-selected="setPerson"
          id="assigned_to"
          name="assigned_to"
          :placeholder="$tc('phrases.assignTo', 2)"
        >
          <md-option value>-- {{ $tc("phrases.anyUser") }} --</md-option>
          <md-option
            :key="person.id"
            :value="person.id"
            v-for="person in ticketUserService.list"
          >
            {{ person.name }}
          </md-option>
        </md-select>
      </md-field>
    </div>

    <div class="md-layout-item md-size-33 md-small-size-100">
      <md-autocomplete
        v-model="customerSearchTerm"
        :md-options="customerOptions"
        @md-changed="searchCustomers"
        @md-selected="setCustomer"
      >
        <label>{{ $tc("phrases.searchCustomer") }}</label>
        <template slot="md-autocomplete-item" slot-scope="{ item }">
          {{ item.name }}
        </template>
      </md-autocomplete>
    </div>

    <div class="md-layout-item md-size-100">
      <md-button @click="filterTickets" class="md-raised md-primary">
        {{ $tc("words.filter") }}
      </md-button>
      <md-button class="md-raised md-accent" @click="closeFilter()">
        {{ $tc("words.close") }}
      </md-button>
    </div>
  </div>
</template>

<script>
import { notify } from "@/mixins/notify.js"
import { PersonService } from "@/services/PersonService.js"
import { TicketService } from "@/services/TicketService.js"
import { TicketUserService } from "@/services/TicketUserService.js"
import { EventBus } from "@/shared/eventbus.js"

export default {
  name: "Filtering",
  mixins: [notify],
  mounted() {
    this.getCategories()
    this.getPeople()
  },
  data() {
    return {
      personService: new PersonService(),
      ticketService: new TicketService(),
      ticketUserService: new TicketUserService(),
      selectedCategory: "",
      selectedPerson: "",
      selectedCustomer: null,
      customerSearchTerm: "",
      customerOptions: [],
    }
  },
  computed: {
    // editing the text after picking a customer un-picks them, so a stale id
    // is never sent
    isCustomerSelected() {
      return (
        this.selectedCustomer !== null &&
        this.customerSearchTerm === this.selectedCustomer.name
      )
    },
  },
  methods: {
    setCategory(category) {
      this.selectedCategory = category
    },
    setPerson(person) {
      this.selectedPerson = person
    },
    setCustomer(customer) {
      this.selectedCustomer = customer
      this.customerSearchTerm = customer.name
    },
    async searchCustomers(term) {
      try {
        this.customerOptions =
          await this.personService.searchCustomerOptions(term)
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async getCategories() {
      try {
        await this.ticketService.getCategories()
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async getPeople() {
      try {
        await this.ticketUserService.getUsers()
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    filterTickets() {
      let query = ""
      if (this.selectedCategory && this.selectedCategory !== "") {
        query += "&category=" + this.selectedCategory
      }
      if (this.selectedPerson && this.selectedPerson !== "") {
        query += "&person=" + this.selectedPerson
      }
      if (this.isCustomerSelected) {
        query += "&customer=" + this.selectedCustomer.id
      }
      this.$emit("filtering", query)
    },
    closeFilter() {
      EventBus.$emit("filterClosed")
    },
  },
}
</script>

<style scoped lang="scss"></style>
