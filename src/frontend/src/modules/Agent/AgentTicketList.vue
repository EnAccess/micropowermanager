<template>
  <widget
    :subscriber="subscriber"
    :title="$tc('phrases.agentTicket', 1)"
    :paginator="ticketList.paginator"
    color="primary"
  >
    <ticket-item
      :allow-lock="false"
      :ticket-list="ticketList.list"
    ></ticket-item>
  </widget>
</template>
<script>
import { resources } from "@/resources.js"
import { TicketList } from "@/services/TicketService.js"
import { EventBus } from "@/shared/eventbus.js"
import TicketItem from "@/shared/TicketItem.vue"
import Widget from "@/shared/Widget.vue"

export default {
  name: "AgentTicketList",
  data() {
    return {
      ticketList: new TicketList(resources.agents.tickets + "/" + this.agentId),
      subscriber: "AgentTickets",
    }
  },
  components: {
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
