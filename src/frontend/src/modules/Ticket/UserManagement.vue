<template>
  <div class="row">
    <div v-if="showNewUser" style="margin-top: 1rem"></div>
    <widget
      :title="$tc('phrases.userList')"
      :button="false"
      button-text="Add new User"
      @widgetAction="showAddUser"
      color="green"
      :subscriber="subscriber"
    >
      <md-table
        v-model="ticketUserService.list"
        md-sort="name"
        md-sort-order="asc"
        md-card
      >
        <md-table-row slot="md-table-row" slot-scope="{ item }">
          <md-table-cell :md-label="$tc('words.id')" md-sort-by="id" md-numeric>
            {{ item.id }}
          </md-table-cell>
          <md-table-cell :md-label="$tc('words.name')" md-sort-by="name">
            {{ item.name }}
          </md-table-cell>
          <md-table-cell :md-label="$tc('words.tag')" md-sort-by="tag">
            <input
              type="checkbox"
              :checked="item.isTicketingUser"
              @change="(e) => updateTicketingUser(e.target.checked, item.id)"
            />
          </md-table-cell>
          <md-table-cell
            :md-label="$tc('phrases.createdDate')"
            md-sort-by="created_at"
          >
            {{ item.created_at }}
          </md-table-cell>
        </md-table-row>
      </md-table>
    </widget>
  </div>
</template>

<script>
import { notify } from "@/mixins/notify"
import Widget from "@/shared/Widget.vue"
import { TicketUserService } from "@/services/TicketUserService"
import { EventBus } from "@/shared/eventbus"

export default {
  name: "UserManagement",
  mixing: [notify],
  components: { Widget },
  data() {
    return {
      subscriber: "ticket-user-list",
      ticketUserService: new TicketUserService(),
      showNewUser: false,
      loading: false,
      updateModal: false,
    }
  },
  mounted() {
    this.getUsers()
    EventBus.$on("ticket.add.user", function (data) {
      this.showNewUser = data
    })
  },
  methods: {
    async getUsers() {
      try {
        this.ticketUserService.list = []
        await this.ticketUserService.getUsers()
        EventBus.$emit(
          "widgetContentLoaded",
          this.subscriber,
          this.ticketUserService.list.length,
        )
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    showAddUser() {
      this.showNewUser = true
    },
    updateTicketingUser(isActivated, userId) {
      console.log(isActivated, userId)
    },
  },
}
</script>

<style scoped></style>
