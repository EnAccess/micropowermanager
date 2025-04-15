<template>
  <div>
    <widget
      :button="true"
      :button-text="$tc('words.filter')"
      @widgetAction="
        () => {
          filterTicket = true
        }
      "
      :title="$tc('words.ticket', 2)"
      button-icon="filter_list"
    >
      <div class="md-layout-item" v-if="filterTicket">
        <filtering @filtering="filtered"></filtering>
      </div>
      <div class="md-layout md-gutter">
        <div class="md-layout-item md-size-50 md-medium-size-100">
          <widget
            :title="$tc('phrases.openTicket')"
            :subscriber="subscriber.opened"
            :paginator="ticketService.openedPaginator"
            :resetKey="resetKey"
            color="green"
          >
            <ticket-item
              :allow-comment="true"
              :ticket-list="ticketService.openedList"
              :table-heads="tableHeads"
            ></ticket-item>
          </widget>
        </div>
        <div class="md-layout-item md-size-50 md-medium-size-100">
          <widget
            :title="$tc('phrases.closedTicket')"
            :subscriber="subscriber.closed"
            :paginator="ticketService.closedPaginator"
            :resetKey="resetKey"
            color="red"
          >
            <ticket-item
              :allow-comment="true"
              :ticket-list="ticketService.closedList"
              :table-heads="tableHeads"
            ></ticket-item>
          </widget>
        </div>
      </div>
    </widget>
  </div>
</template>

<script>
import Widget from "@/shared/Widget.vue"
import TicketItem from "@/shared/TicketItem"
import { EventBus } from "@/shared/eventbus"
import Filtering from "@/modules/Ticket/Filtering"
import { resources } from "@/resources"
import { TicketService } from "@/services/TicketService"
import { baseUrl } from "@/repositories/Client/AxiosClient"
export default {
  name: "Tickets",
  components: { Filtering, Widget, TicketItem },
  data() {
    return {
      ticketService: new TicketService(),
      loading: true,
      filterTicket: false,
      tableHeads: [
        this.$tc("words.subject"),
        this.$tc("words.category"),
        this.$tc("words.date"),
      ],
      resetKey: 0,
      subscriber: {
        opened: "ticketListOpened",
        closed: "ticketListClosed",
      },
    }
  },
  mounted() {
    EventBus.$on("pageLoaded", (a, b) => this.reloadList(a, b))
    EventBus.$on("filterClosed", () => {
      this.filterTicket = false
    })
    EventBus.$on("listChanged", () => {
      this.resetKey += 1
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber.opened,
        this.ticketService.openedList?.length ?? 0,
      )
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber.closed,
        this.ticketService.closedList?.length ?? 0,
      )
    })
  },
  beforeDestroy() {
    EventBus.$off("pageLoaded", this.reloadList)
  },
  methods: {
    async reloadList(subscriber, data) {
      if (
        subscriber !== "ticketListOpened" &&
        subscriber !== "ticketListClosed"
      ) {
        return
      }
      await this.ticketService.updateList(data, subscriber)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber.opened,
        this.ticketService.openedList?.length ?? 0,
      )
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber.closed,
        this.ticketService.closedList?.length ?? 0,
      )
    },
    filtered(data) {
      this.ticketService.openedPaginator.setPaginationBaseUrl(
        baseUrl + resources.ticket.list + "?status=0" + data,
      )
      this.ticketService.openedPaginator.loadPage(1).then((response) => {
        this.reloadList(this.subscriber.opened, response.data)
      })
      this.ticketService.closedPaginator.setPaginationBaseUrl(
        baseUrl + resources.ticket.list + "?status=1" + data,
      )
      this.ticketService.closedPaginator.loadPage(1).then((response) => {
        this.reloadList(this.subscriber.closed, response.data)
      })
    },
  },
}
</script>

<style scoped>
.ticket-list-card-r {
  margin-inline-end: 2vh;
  margin-top: 2vh;
}

.ticket-list-card-l {
  margin-inline-start: 2vh;
  margin-top: 2vh;
}

.no-ticket {
  padding: 30px;
  margin-top: 5vh;
  background: #8c8c8c;
  color: white;
}

.o-ticket {
  background-color: #8eb18e;
  padding: 5px;
  font-size: larger;
  font-weight: bold;
  color: white;
}

.c-ticket {
  background-color: #9a0325;
  padding: 5px;
  font-size: larger;
  font-weight: bold;
  color: white;
}
</style>
