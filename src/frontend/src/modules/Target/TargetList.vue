<template>
  <div>
    <widget
      :id="'target-list'"
      :title="$tc('words.target', 2)"
      :button="true"
      :buttonText="'New Target'"
      :paginator="targets.paginator"
      :subscriber="subscriber"
      @widgetAction="newTarget"
      color="green"
    >
      <!-- list of targets -->
      <md-table>
        <md-table-row>
          <md-table-head :colspan="expandedRow >= 0 ? 3 : 1">
            {{ $tc("words.period") }}
          </md-table-head>
          <md-table-head>{{ $tc("words.for") }}</md-table-head>
          <md-table-head>
            {{ $tc("phrases.subTargets") }}
          </md-table-head>
        </md-table-row>
        <template v-for="(target, index) in targets.list">
          <md-table-row :key="index">
            <md-table-cell :colspan="expandedRow >= 0 ? 3 : 1">
              {{ target.target.targetDate }}
            </md-table-cell>
            <md-table-cell>
              {{ target.target.owner?.name || "Unknown" }} ({{
                target.owner || "N/A"
              }})
            </md-table-cell>
            <md-table-cell
              v-if="target.target.subTargets.length > 0"
              style="cursor: pointer"
            >
              <div v-if="index === expandedRow" @click="collapseTarget()">
                <md-icon>arrow_drop_down</md-icon>
                {{ $tc("words.collapse") }}
              </div>
              <div v-else @click="expandTarget(index)">
                <md-icon>arrow_right</md-icon>
                {{ $tc("words.expand") }}
              </div>
            </md-table-cell>
            <md-table-cell v-else>-</md-table-cell>
          </md-table-row>
          <template v-if="index === expandedRow">
            <md-table-row
              v-for="(subTarget, subIndex) in target.target.subTargets"
              :key="subIndex"
            >
              <md-table-cell>
                {{ subTarget.connections?.name || "N\A" }}
              </md-table-cell>
              <md-table-cell>
                {{ $tc("words.revenue") }}
              </md-table-cell>
              <md-table-cell>
                {{ subTarget.revenue }}
              </md-table-cell>
              <md-table-cell>
                {{ $tc("phrases.newConnection", 2) }}
              </md-table-cell>
              <md-table-cell>
                {{ subTarget.newConnections }}
              </md-table-cell>
            </md-table-row>
          </template>
        </template>
      </md-table>
    </widget>
  </div>
</template>

<script>
import Widget from "@/shared/Widget.vue"
import { Targets } from "@/services/TargetService"
import { EventBus } from "@/shared/eventbus"

export default {
  name: "TargetList",
  components: {
    Widget,
  },
  computed: {
    expandedTarget: function () {
      return this.expandedRow !== null ? this.expandedRow : -1
    },
  },
  created() {},
  mounted() {
    EventBus.$emit("bread", this.bcd)
    EventBus.$on("pageLoaded", this.reloadList)
    EventBus.$on("searching", this.searching)
    EventBus.$on("end_searching", this.endSearching)
  },
  data() {
    return {
      expandedRow: null,
      targets: new Targets(),
      subscriber: "targets",
      headers: ["Period", "For", "Sub Targets"],
      tableName: "Target",
    }
  },
  methods: {
    reloadList(subscriber, data) {
      if (subscriber !== this.subscriber) return
      this.targets.updateList(data)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.targets.list.length,
      )
    },
    expandTarget(index) {
      let subTarget = this.targets.targetAtIndex(index)
      if (subTarget !== null) {
        this.expandedRow = index
      }
    },
    collapseTarget() {
      this.expandedRow = null
    },
    newTarget() {
      this.$router.push({ path: "/targets/new" })
    },
  },
}
</script>

<style scoped></style>
