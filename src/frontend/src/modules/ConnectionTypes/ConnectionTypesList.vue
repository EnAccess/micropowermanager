<template>
  <div>
    <NewConnectionType />
    <widget
      :id="'connection-types-list'"
      :title="$tc('phrases.connectionType', 2)"
      :paginator="connectionTypes.paginator"
      :subscriber="subscriber"
      :button="true"
      :button-text="$tc('phrases.newConnectionType')"
      @widgetAction="addNew"
      :color="'green'"
    >
      <md-table md-card style="margin-left: 0">
        <md-table-row>
          <md-table-head>#</md-table-head>
          <md-table-head>{{ $tc("words.id") }}</md-table-head>
          <md-table-head>{{ $tc("words.name") }}</md-table-head>
        </md-table-row>
        <md-table-row
          v-for="(type, index) in connectionTypes"
          :key="type.id"
          @click="connectionTypeDetail(type)"
          style="cursor: pointer"
        >
          <md-table-cell>{{ index + 1 }}</md-table-cell>
          <md-table-cell>{{ type.id }}</md-table-cell>
          <md-table-cell>{{ type.name }}</md-table-cell>
        </md-table-row>
      </md-table>
    </widget>
  </div>
</template>

<script>
import Widget from "@/shared/Widget.vue"
import { EventBus } from "@/shared/eventbus"
import { ConnectionTypeService } from "@/services/ConnectionTypeService"
import { SubConnectionTypeService } from "@/services/SubConnectionTypeService"
import NewConnectionType from "./NewConnectionType"
import { notify } from "@/mixins/notify"

export default {
  name: "ConnectionTypesList",
  mixins: [notify],
  components: { Widget, NewConnectionType },
  mounted() {
    EventBus.$on("pageLoaded", this.reloadList)
    EventBus.$on("searching", this.searching)
    EventBus.$on("end_searching", this.endSearching)
    EventBus.$on("connectionTypeAdded", this.getConnectionTypes)
    this.getConnectionTypes()
  },

  data() {
    return {
      connectionTypeService: new ConnectionTypeService(),
      subConnectionTypeService: new SubConnectionTypeService(),
      subscriber: "connection-types-list",
      connectionTypes: [],
      subConnectionTypes: [],
    }
  },
  methods: {
    connectionTypeDetail(type) {
      this.$router.push({ path: "/settings/connection-types/" + type.id })
      EventBus.$emit("connectionTypeDetail", type.name)
    },
    reloadList(subscriber, data) {
      if (subscriber !== this.subscriber) return
      this.connectionTypes = this.connectionTypeService.updateList(data)
    },
    async getConnectionTypes() {
      try {
        this.connectionTypes =
          await this.connectionTypeService.getConnectionTypes()
        EventBus.$emit(
          "widgetContentLoaded",
          this.subscriber,
          this.connectionTypes.length,
        )
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    addNew() {
      EventBus.$emit("showNewConnectionType")
    },
  },
}
</script>

<style scoped></style>
