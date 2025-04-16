<template>
  <widget :title="$tc('phrases.newTarget')" id="new-target" color="red">
    <md-card>
      <md-card-content>
        <div class="md-layout md-gutter">
          <div class="md-layout-item md-size-100" v-if="dataIsLoading">
            <md-progress-bar md-mode="indeterminate" />
          </div>
          <div class="md-layout-item">
            <md-field
              :class="{
                'md-invalid': errors.has($tc('phrases.assignFor', 2)),
              }"
            >
              <label>{{ $tc("phrases.assignFor", 2) }}</label>
              <md-select
                :name="$tc('phrases.assignFor', 2)"
                v-model="targetAssignType"
                @md-selected="onTargetTypeChange"
                v-validate="'required'"
              >
                <md-option selected disabled>
                  -- {{ $tc("phrases.newTarget", 2) }} --
                </md-option>
                <md-option value="cluster">
                  {{ $tc("words.cluster") }}
                </md-option>
                <md-option value="mini-grid">
                  {{ $tc("words.miniGrid") }}
                </md-option>
              </md-select>
              <span class="md-error">
                {{ errors.first($tc("phrases.assignFor", 2)) }}
              </span>
            </md-field>
          </div>

          <div class="md-layout-item">
            <md-field
              :class="{
                'md-invalid': errors.has($tc('phrases.assignTo', 2)),
              }"
            >
              <label>{{ $tc("phrases.assignTo", 2) }}</label>
              <md-select
                :disabled="
                  targetDestinations.length === 0 || dataIsLoading === true
                "
                v-model="targetAssignId"
                v-validate="'required'"
                :name="$tc('phrases.assignTo', 2)"
              >
                <md-option selected disabled>
                  -- {{ $tc("phrases.newTarget", 2) }} --
                </md-option>
                <md-option
                  v-for="(targetDestination, index) in targetDestinations"
                  :key="index"
                  :value="targetDestination.id"
                >
                  {{ targetDestination.name }}
                </md-option>
              </md-select>
              <span class="md-error">
                {{ errors.first($tc("phrases.assignTo", 2)) }}
              </span>
            </md-field>
          </div>

          <div class="md-layout-item">
            <md-datepicker
              :class="{
                'md-invalid': errors.has($tc('phrases.validUntil')),
              }"
              :name="$tc('phrases.validUntil')"
              md-immediately
              v-model="targetValidUntil"
              v-validate.initial="'required'"
              :md-close-on-blur="false"
            >
              <label>{{ $tc("phrases.validUntil") }}</label>
              <span class="md-error">
                {{ errors.first($tc("phrases.validUntil")) }}
              </span>
            </md-datepicker>
          </div>
        </div>

        <div class="new-target" v-if="!isMobile">
          <h3>{{ $tc("phrases.targetsForConnectionTypes") }}</h3>
          <md-table>
            <md-table-row
              v-for="connection in connectionTypes.list"
              :key="connection.id"
            >
              <md-table-cell>{{ connection.name }}</md-table-cell>
              <md-table-cell>
                <md-field>
                  <label>
                    {{ $tc("phrases.newConnection", 2) }}
                  </label>
                  <md-input
                    type="number"
                    class="form-control full-width"
                    v-model="connection.target.newConnection"
                  />
                </md-field>
              </md-table-cell>

              <md-table-cell>
                <md-field>
                  <label>
                    {{ $tc("phrases.connectedPower") }}
                  </label>
                  <md-input
                    type="number"
                    class="form-control full-width"
                    v-model="connection.target.connectedPower"
                  />
                </md-field>
              </md-table-cell>

              <md-table-cell>
                <md-field>
                  <label>
                    {{ $tc("words.energy") }}(kWh) /
                    {{ $tc("words.month") }}
                  </label>
                  <md-input
                    type="number"
                    class="form-control full-width"
                    v-model="connection.target.energyPerMonth"
                  />
                </md-field>
              </md-table-cell>
              <md-table-cell>
                <md-field>
                  <label>
                    {{ $tc("words.revenue") }} /
                    {{ $tc("words.month") }}
                  </label>
                  <md-input
                    type="number"
                    class="form-control full-width"
                    v-model="connection.target.totalRevenue"
                  />
                </md-field>
              </md-table-cell>
              <md-table-cell>
                <md-field>
                  <label>
                    {{ $tc("phrases.avgRevenue") }} /
                    {{ $tc("words.month") }}
                  </label>
                  <md-input
                    type="text"
                    class="form-control full-width"
                    v-model="connection.target.averageRevenuePerMonth"
                  />
                </md-field>
              </md-table-cell>
              <md-table-cell>
                {{
                  addCustomers(
                    connection.target.newConnection,
                    numberOfCustomers.findConnectionCustomers(connection.id),
                  )
                }}
              </md-table-cell>
            </md-table-row>
            <md-table-row>
              <md-table-cell>
                {{ $tc("words.total") }}
              </md-table-cell>
              <md-table-cell>
                {{ total["newConnection"] }}
              </md-table-cell>
              <md-table-cell>
                {{ total["connectedPower"] }}
              </md-table-cell>
              <md-table-cell>
                {{ total["energyPerMonth"] }}
              </md-table-cell>
              <md-table-cell>
                {{ readable(total["totalRevenue"]) }}
              </md-table-cell>
              <md-table-cell>
                {{
                  readable(
                    total["totalRevenue"] / total["totalCustomers"] +
                      total["newConnection"],
                  )
                }}
              </md-table-cell>
              <md-table-cell>
                {{ readable(total["totalCustomers"] + total["newConnection"]) }}
              </md-table-cell>
            </md-table-row>
          </md-table>
        </div>

        <div class="page-container" v-if="isMobile">
          <md-app
            md-mode="fixed"
            v-for="connection in connectionTypes.list"
            :key="connection.id"
          >
            <md-app-toolbar>
              <span class="md-subheading">
                {{ connection.name }}
              </span>
            </md-app-toolbar>
            <md-app-content>
              <div class="md-layout-item md-size-100">
                <md-field>
                  <label>
                    {{ $tc("phrases.newConnection", 2) }}
                  </label>
                  <md-input
                    type="number"
                    class="form-control full-width"
                    v-model="connection.target.newConnection"
                  />
                </md-field>
              </div>

              <div class="md-layout-item md-size-100">
                <md-field>
                  <label>
                    {{ $tc("phrases.connectedPower") }}
                  </label>
                  <md-input
                    type="number"
                    class="form-control full-width"
                    v-model="connection.target.connectedPower"
                  />
                </md-field>
              </div>
              <div class="md-layout-item md-size-100">
                <md-field>
                  <label>
                    {{ $tc("words.energy") }}(kWh) /
                    {{ $tc("words.month") }}
                  </label>
                  <md-input
                    type="number"
                    class="form-control full-width"
                    v-model="connection.target.energyPerMonth"
                  />
                </md-field>
              </div>
              <div class="md-layout-item md-size-100">
                <md-field>
                  <label>
                    {{ $tc("words.revenue") }} /
                    {{ $tc("words.month") }}
                  </label>
                  <md-input
                    type="number"
                    class="form-control full-width"
                    v-model="connection.target.totalRevenue"
                  />
                </md-field>
              </div>
              <div class="md-layout-item md-size-100">
                <md-field>
                  <label>
                    {{ $tc("phrases.avgRevenue") }} /
                    {{ $tc("words.month") }}
                  </label>
                  <md-input
                    type="text"
                    class="form-control full-width"
                    v-model="connection.target.averageRevenuePerMonth"
                  />
                </md-field>
              </div>
              <div class="md-layout-item md-size-100">
                =
                {{
                  addCustomers(
                    connection.target.newConnection,
                    numberOfCustomers.findConnectionCustomers(connection.id),
                  )
                }}
                (New + Registered)
              </div>
            </md-app-content>
          </md-app>
          <div class="md-subheading" style="float: right; right: 0">
            Total =
            {{ readable(total["totalCustomers"] + total["newConnection"]) }}
          </div>
        </div>
      </md-card-content>
      <md-card-actions>
        <md-button class="md-dense md-raised md-primary" @click="submitTarget">
          <md-icon>save</md-icon>
          {{ $tc("words.save") }}
        </md-button>
      </md-card-actions>
    </md-card>
  </widget>
</template>

<script>
import Widget from "@/shared/Widget.vue"
import {
  ConnectionTypes,
  NumberOfCustomers,
} from "@/services/ConnectionTypeService"
import { currency } from "@/mixins/currency"
import { Targets } from "@/services/TargetService"
import { baseUrl } from "@/repositories/Client/AxiosClient"
import { resources } from "@/resources"
import { notify } from "@/mixins/notify"

export default {
  name: "NewTarget",
  components: { Widget },
  mixins: [currency, notify],
  computed: {
    isMobile() {
      return this.$store.getters["resolution/getDevice"]
    },
    total: {
      cache: false,
      deep: true,
      get: function () {
        let total = {
          averageRevenuePerMonth: 0,
          connectedPower: 0,
          energyPerMonth: 0,
          newConnection: 0,
          totalRevenue: 0,
          totalCustomers: this.numberOfCustomers.total,
        }

        if (this.connectionTypes.list.length === 0) {
          return total
        }

        this.connectionTypes.list.reduce(function (prev, next) {
          prev["averageRevenuePerMonth"] += parseInt(
            next["target"]["averageRevenuePerMonth"],
          )
          prev["connectedPower"] += parseInt(next["target"]["connectedPower"])
          prev["energyPerMonth"] += parseInt(next["target"]["energyPerMonth"])
          prev["newConnection"] += parseInt(next["target"]["newConnection"])
          prev["totalRevenue"] += parseInt(next["target"]["totalRevenue"])
          return prev
        }, total)
        return total
      },
    },
  },
  mounted() {
    this.connectionTypes.getConnectionTypes()
    this.numberOfCustomers.getList()
  },

  data() {
    return {
      dataIsLoading: false,
      targetDestinations: [], // mini-grid or cluster list
      targetAssignType: null, // determines if the target is whether for a mini-grip or for a cluster.
      targetAssignId: null, //the id of the mini-grid or cluster
      connectionTypes: new ConnectionTypes(),
      numberOfCustomers: new NumberOfCustomers(),
      targets: new Targets(),
      targetValidUntil: new Date(),
    }
  },
  methods: {
    async submitTarget() {
      let validation = await this.$validator.validateAll()
      if (!validation) {
        this.alertNotify("warn", this.$tc("phrases.newTargetNotify", 1))
        return
      }
      try {
        await this.targets.store(
          this.targetValidUntil,
          this.targetAssignType,
          this.targetAssignId,
          this.connectionTypes.list,
        )
        this.$swal("Success", this.$tc("phrases.newTargetNotify", 2), "success")
        this.$router.push({ path: "/targets" })
      } catch (e) {
        this.alertNotify("error", e.message)
      }

      //success message
    },
    addCustomers(newConnections, connections) {
      return parseInt(newConnections) + parseInt(connections)
    },

    onTargetTypeChange(value) {
      this.dataIsLoading = true
      if (value === "mini-grid") {
        //get list of mini-grids
        this.updateTargetDestination(baseUrl + resources.miniGrids.list)
      } else {
        //get list of clusters
        this.updateTargetDestination(baseUrl + resources.clusters.list)
      }
    },

    updateTargetDestination(url) {
      axios.get(url).then((response) => {
        this.targetDestinations = response.data.data
        this.dataIsLoading = false // hide progress bar
      })
    },
  },
}
</script>

<style lang="scss" scoped>
.full-width {
  width: 100%;
}

.mt {
  margin-top: 10px;
}

.divider {
  border-right: 1px dashed;
  padding-right: 10px;
}

.row-head {
  position: -webkit-sticky;
  position: sticky;
  top: 0 !important;
  z-index: 1;
  background-color: #ffffcc;
}
</style>
