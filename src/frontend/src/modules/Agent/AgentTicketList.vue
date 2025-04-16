<template>
  <widget
    :subscriber="subscriber"
    :title="$tc('phrases.agentTicket', 1)"
    :paginator="agentTicketService.paginator"
    color="green"
  >
    <ticket-item
      :allow-comment="true"
      :allow-lock="false"
      :table-heads="tableHeads"
      :ticket-list="agentTicketService.list"
    ></ticket-item>
  </widget>
</template>
<script>
import Widget from "@/shared/Widget.vue"
import { AgentTicketService } from "@/services/AgentTicketService"
import { EventBus } from "@/shared/eventbus"
import { UserTickets } from "@/services/TicketService"
import TicketItem from "../../shared/TicketItem"

export default {
  name: "AgentTicketList",
  data() {
    return {
      loaded: false,
      agentTicketService: new AgentTicketService(this.agentId),
      subscriber: "AgentTickets",
      showTicket: null,
      tableHeads: [
        this.$tc("words.subject"),
        this.$tc("words.category"),
        this.$tc("words.status"),
        this.$tc("words.date"),
      ],
      tickets: new UserTickets(this.$store.getters.person.id),
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
    async reloadList(subscriber, data) {
      if (subscriber !== this.subscriber) {
        return
      }
      await this.agentTicketService.updateList(data)
      this.loaded = true
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.agentTicketService.list.length,
      )
    },
  },
}
</script>
<style scoped></style>
