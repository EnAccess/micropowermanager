<template>
  <div>
    <widget
      :subscriber="subscriber"
      :title="$tc('phrases.agentTicket', 1)"
      :paginator="ticketList.paginator"
      :button="true"
      :button-text="$tc('phrases.newTicket')"
      :reset-key="resetKey"
      color="primary"
      @widgetAction="showModal = true"
    >
      <ticket-item
        :allow-lock="false"
        :ticket-list="ticketList.list"
      ></ticket-item>
    </widget>

    <new-ticket-dialog
      :active="showModal"
      :resource="ticketResource"
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
  name: "AgentTicketList",
  data() {
    return {
      ticketResource: resources.agents.tickets + "/" + this.agentId,
      ticketList: new TicketList(resources.agents.tickets + "/" + this.agentId),
      subscriber: "AgentTickets",
      showModal: false,
      resetKey: 0,
    }
  },
  components: {
    NewTicketDialog,
    TicketItem,
    Widget,
  },
  props: {
    agentId: {
      default: null,
    },
  },
  mounted() {
    EventBus.$on("pageLoaded", this.reloadList)
  },
  beforeDestroy() {
    EventBus.$off("pageLoaded", this.reloadList)
  },
  methods: {
    reloadList(subscriber, data) {
      if (subscriber !== this.subscriber) {
        return
      }
      this.ticketList.updateList(data)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.ticketList.list.length,
      )
    },
  },
}
</script>
<style scoped lang="scss"></style>
