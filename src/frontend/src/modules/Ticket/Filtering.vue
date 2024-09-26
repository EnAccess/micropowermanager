<template>
  <div
    class="md-layout md-gutter md-size-100"
    style="padding: 0.4rem; margin: auto"
  >
    <div class="md-layout-item md-size-42 md-small-size-100">
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

    <div class="md-layout-item md-size-42 md-small-size-100">
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

    <div class="md-layout-item md-size-16 md-small-size-100">
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
import { EventBus } from "@/shared/eventbus"
import { TicketService } from "@/services/TicketService"
import { TicketUserService } from "@/services/TicketUserService"

export default {
  name: "Filtering",
  created() {},
  mounted() {
    this.getCategories()
    this.getPeople()
  },
  data() {
    return {
      ticketService: new TicketService(),
      ticketUserService: new TicketUserService(),
      selectedCategory: "",
      selectedPerson: "",
    }
  },
  methods: {
    setCategory(category) {
      this.selectedCategory = category
    },
    setPerson(person) {
      this.selectedPerson = person
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
      this.$emit("filtering", query)
    },
    closeFilter() {
      EventBus.$emit("filterClosed")
    },
  },
}
</script>

<style scoped>
.chic-button {
  background-color: #0a0a0c !important;
  color: #fefefe !important;
}

.filter-grid {
  padding: 1rem;
}
</style>
