<template>
  <div>
    <widget :title="$tc('phrases.newCluster', 1)" color="green">
      <md-card class="md-layout-item md-size-100">
        <md-card-content>
          <div class="md-layout md-gutter">
            <div
              class="md-layout-item md-large-size-33 md-medium-size-33 md-small-size-100"
            >
              <md-field
                :class="{
                  'md-invalid': errors.has($tc('words.name')),
                }"
              >
                <label>{{ $tc("words.name") }}</label>
                <md-input
                  v-model="clusterName"
                  :name="$tc('words.name')"
                  id="clusterName"
                  v-validate="'required|min:3'"
                />

                <span class="md-error">
                  {{ errors.first($tc("words.name")) }}
                </span>
              </md-field>
            </div>
            <div
              class="md-layout-item md-large-size-33 md-medium-size-33 md-small-size-100"
            >
              <user-list @userSelected="userSelected"></user-list>
            </div>
            <div
              class="md-layout-item md-large-size-33 md-medium-size-33 md-small-size-100"
            >
              <md-button class="md-primary save-button" @click="saveCluster()">
                {{ $tc("words.save") }}
              </md-button>
            </div>
            <div class="md-layout-item md-size-100">
              <md-list>
                <div v-if="mappingService.searchedOrDrawnItems.length > 0">
                  <md-subheader v-if="typed && clusterName !== ''">
                    Search results for {{ clusterName }}
                  </md-subheader>
                  <md-list-item
                    style="cursor: pointer"
                    :key="index"
                    v-for="(geo, index) in mappingService.searchedOrDrawnItems"
                    @click="locationSelected(geo)"
                  >
                    <md-icon
                      :class="{
                        'selected-list-item': geo.selected,
                      }"
                    >
                      location_on
                    </md-icon>
                    <md-icon
                      v-if="geo.draw_type === 'draw'"
                      :class="{
                        'selected-list-item': geo.selected,
                      }"
                    >
                      edit
                    </md-icon>

                    <span class="md-list-item-text">
                      {{ geo.display_name }}
                    </span>
                  </md-list-item>
                </div>
                <div
                  v-if="
                    mappingService.searchedOrDrawnItems.length < 1 &&
                    typed &&
                    clusterName !== ''
                  "
                >
                  <h4 style="color: #797979; margin-left: 1rem">
                    {{
                      $tc("phrases.newCluster", 2, {
                        clusterName: clusterName,
                      })
                    }}
                  </h4>
                </div>
              </md-list>
            </div>
            <div class="md-layout-item md-size-100 map-area">
              <cluster-map
                ref="clusterMapRef"
                :mapping-service="mappingService"
                :polygon="true"
                :edit="true"
                :remove="true"
                @customDrawnDeleted="customDrawnDeletedSet"
                @customDrawnEdited="customDrawnEditedSet"
              />
            </div>
          </div>
        </md-card-content>
      </md-card>
    </widget>

    <md-dialog
      :md-active.sync="dialogActive"
      :md-close-on-esc="false"
      :md-click-outside-to-close="false"
    >
      <md-dialog-title>
        {{ $tc("phrases.namingCluster") }}
      </md-dialog-title>
      <md-dialog-content>
        <div class="md-layout md-gutter">
          <div
            class="md-layout-item md-large-size-100 md-medium-size-100 md-small-size-100"
          >
            <p>
              {{ $tc("phrases.newClusterNotify", 0) }}
            </p>
          </div>
          <div
            class="md-layout-item md-large-size-100 md-medium-size-100 md-small-size-100"
          >
            <md-field
              :class="{
                'md-invalid': errors.has($tc('words.name')),
              }"
            >
              <label>{{ $tc("words.name") }}</label>
              <md-input
                v-model="clusterName"
                :name="$tc('words.name')"
                v-validate="'required|min:3'"
              />

              <span class="md-error">
                {{ errors.first($tc("words.name")) }}
              </span>
            </md-field>
          </div>
          <div
            class="md-layout-item md-large-size-100 md-medium-size-100 md-small-size-100"
          >
            <md-button class="md-primary save-button" @click="saveCluster()">
              {{ $tc("words.save") }}
            </md-button>
          </div>
        </div>
      </md-dialog-content>
    </md-dialog>
  </div>
</template>

<script>
import { EventBus } from "@/shared/eventbus"
import { MappingService } from "@/services/MappingService"
import { ClusterService } from "@/services/ClusterService"
import { notify } from "@/mixins/notify"
import ClusterMap from "@/modules/Map/ClusterMap.vue"
import UserList from "@/modules/Dashboard/UserList.vue"
import Widget from "@/shared/Widget.vue"

export default {
  name: "AddCluster",
  mixins: [notify],
  components: {
    ClusterMap,
    Widget,
    UserList,
  },
  data() {
    return {
      clusterService: new ClusterService(),
      mappingService: new MappingService(),
      geoData: null,
      clusterName: null,
      user: null,
      selectedCluster: null,
      geoDataItems: [],
      typed: false,
      filteredTypes: { polygon: true },
      dialogActive: false,
    }
  },
  mounted() {
    this.mappingService.setCenter([
      this.$store.getters["settings/getMapSettings"].latitude,
      this.$store.getters["settings/getMapSettings"].longitude,
    ])
    EventBus.$on("customDrawnSet", (geoDataItem) => {
      this.customClusterDrawnSet(geoDataItem)
    })
  },
  methods: {
    async searchGeoDataByName() {
      await this.mappingService.getSearchResult(
        this.clusterName,
        this.filteredTypes,
      )
    },
    async locationSelected(geoDataItem) {
      this.mappingService.searchedOrDrawnItems =
        this.mappingService.searchedOrDrawnItems.map((item) => {
          item.selected = item.display_name === geoDataItem.display_name
          return item
        })
      this.selectedCluster = geoDataItem
      this.mappingService.geoData = geoDataItem
      this.$refs.clusterMapRef.drawCluster()
    },
    async saveCluster() {
      this.dialogActive = false
      if (!this.selectedCluster) {
        this.$swal({
          type: "error",
          title: this.$tc("phrases.newClusterNotify", 1),
          text: this.$tc("phrases.newClusterNotify", 2),
        })
        return
      }
      if (this.user === null) {
        this.$swal({
          type: "error",
          title: this.$tc("phrases.newClusterNotify2", 0),
          text: this.$tc("phrases.newClusterNotify2", 1),
        })
        return
      }
      if (this.clusterName === "Unnamed" || this.clusterName === "") {
        this.dialogActive = true
        return
      }
      try {
        const cluster = {
          geoType: this.selectedCluster.type,
          geoData: this.selectedCluster,
          name: this.clusterName,
          managerId: this.user,
        }
        await this.clusterService.createCluster(cluster)
        this.alertNotify("success", this.$tc("phrases.newClusterNotify2", 2))
        await this.$router.replace("/clusters")
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    userSelected(user) {
      this.user = user
    },
    customClusterDrawnSet(geoDataItem) {
      geoDataItem.display_name =
        this.clusterName === "" ? "Unnamed" : this.clusterName
      this.typed = false
      this.mappingService.searchedOrDrawnItems.push(geoDataItem)
      this.mappingService.searchedOrDrawnItems =
        this.mappingService.searchedOrDrawnItems.map((item) => {
          item.selected = false
          return item
        })
      this.mappingService.geoData = geoDataItem
      this.$refs.clusterMapRef.drawCluster()
    },
    customDrawnDeletedSet(deletedItems) {
      this.mappingService.searchedOrDrawnItems =
        this.mappingService.searchedOrDrawnItems.filter((item) => {
          const drawnItemCoordinates = item.geojson.coordinates[0].map(
            (coord) => {
              return [coord[1], coord[0]]
            },
          )
          return !deletedItems.some(
            (deletedItem) =>
              JSON.stringify(drawnItemCoordinates) ===
              JSON.stringify(deletedItem.feature.geometry.coordinates[0]),
          )
        })
      if (this.mappingService.searchedOrDrawnItems.length === 0) {
        this.clusterName = null
      }
    },
    customDrawnEditedSet(editedItems) {
      editedItems.forEach((item) => {
        const editedGeoDataItem = this.mappingService.searchedOrDrawnItems.find(
          (x) => x.type === item.type,
        )
        if (editedGeoDataItem) {
          editedGeoDataItem.geojson.coordinates = item.geojson.coordinates
          editedGeoDataItem.lat = item.lat
          editedGeoDataItem.lon = item.lon
        }
      })
    },
  },
  watch: {
    async clusterName() {
      if (this.clusterName.length > 3) {
        let selectedCluster = this.geoDataItems.filter(
          (x) => x.selected === true,
        )[0]
        if (
          selectedCluster !== undefined &&
          selectedCluster.display_name === ""
        ) {
          selectedCluster.display_name = this.clusterName
        } else {
          this.typed = true
          await this.searchGeoDataByName()
        }
      }
    },
  },
}
</script>

<style scoped>
.map-area {
  z-index: 1 !important;
}

.save-button {
  background-color: #325932 !important;
  color: #fefefe !important;
  top: 0.5rem;
  float: right;
}

.selected-list-item {
  color: red !important;
}

.cluster-input {
  color: #747474 !important;
}
</style>
