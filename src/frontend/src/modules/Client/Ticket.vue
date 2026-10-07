<template>
  <div class="col-sm-12">
    <widget
      :subscriber="subscriber"
      color="primary"
      :title="$tc('phrases.userTicket', 2)"
      :paginator="tickets.paginator"
      :button="true"
      :button-text="$tc('phrases.newTicket')"
      @widgetAction="showModal = true"
      :resetKey="resetKey"
    >
      <ticket-item
        :allow-lock="false"
        :show-client="false"
        :ticket-list="tickets.list"
      ></ticket-item>
    </widget>

    <new-ticket-dialog
      :active="showModal"
      :resource="resources.ticket.create"
      :owner-id="personId"
      @close="showModal = false"
      @created="resetKey++"
    />
  </div>
</template>

<script>
import { resources } from "@/resources.js"
import { TicketList } from "@/services/TicketService.js"
import { EventBus } from "@/shared/eventbus.js"
import NewTicketDialog from "@/shared/NewTicketDialog.vue"
import TicketItem from "@/shared/TicketItem.vue"
import Widget from "@/shared/Widget.vue"

export default {
  name: "Ticket",
  components: { NewTicketDialog, TicketItem, Widget },
  props: {
    personId: {
      required: true,
    },
  },
  data() {
    return {
      resources,
      subscriber: "userTickets",
      tickets: new TicketList(resources.ticket.getUser + this.personId),
      showModal: false,
      resetKey: 0,
    }
  },
  beforeDestroy() {
    EventBus.$off("pageLoaded", this.reloadList)
  },
  mounted() {
    EventBus.$on("pageLoaded", this.reloadList)
  },
  methods: {
    reloadList(sub, data) {
      if (sub !== this.subscriber) return
      this.tickets.updateList(data)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.tickets.list.length,
      )
    },
  },
}
</script>

<style scoped lang="scss"></style>
