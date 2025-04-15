<template>
  <div>
    <widget :title="$tc('phrases.newMiniGrid')" color="green">
      <md-card>
        <md-card-content>
          <div class="md-layout md-gutter md-size-100">
            <div class="md-layout-item md-size-70 md-small-size-100">
              <md-field
                :class="{
                  'md-invalid': errors.has($tc('words.name')),
                }"
              >
                <label for="miniGrid_name">
                  {{ $tc("words.name") }}
                </label>
                <md-input
                  id="miniGridName"
                  :name="$tc('words.name')"
                  v-model="miniGridName"
                  v-validate="'required|min:3'"
                ></md-input>
                <span class="md-error">
                  {{ errors.first($tc("words.name")) }}
                </span>
              </md-field>
            </div>
            <div class="md-layout-item md-size-30 md-small-size-100">
              <md-field
                :class="{
                  'md-invalid': errors.has($tc('words.cluster')),
                }"
              >
                <label for="clusterName">
                  {{ $tc("words.cluster") }}
                </label>
                <md-select
                  v-model="selectedClusterId"
                  :name="$tc('words.cluster')"
                  id="clusterName"
                  v-validate="'required'"
                >
                  <md-option
                    v-for="cluster in clusterService.list"
                    :value="cluster.id"
                    :key="cluster.id"
                  >
                    {{ cluster.name }}
                  </md-option>
                </md-select>
                <span class="md-error">
                  {{ errors.first($tc("words.cluster")) }}
                </span>
              </md-field>
            </div>
          </div>
          <div class="md-layout md-gutter md-size-100">
            <div
              class="md-layout md-gutter md-size-60 md-small-size-100"
              style="padding-left: 1.5rem !important"
            >
              <form
                class="md-layout md-gutter"
                @submit.prevent="setPoints"
                style="padding-left: 1.5rem !important"
              >
                <div class="md-layout-item md-size-30 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has($tc('words.latitude')),
                    }"
                  >
                    <label for="latitude">
                      {{ $tc("words.latitude") }}
                    </label>
                    <md-input
                      id="latitude"
                      :name="$tc('words.latitude')"
                      maxlength="8"
                      step="any"
                      v-model="miniGridLatLng.lat"
                      v-validate="'required|decimal:5|max:8'"
                    ></md-input>
                    <span class="md-error">
                      {{ errors.first($tc("words.latitude")) }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-30 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has($tc('words.longitude')),
                    }"
                  >
                    <label for="longitude">
                      {{ $tc("words.longitude") }}
                    </label>
                    <md-input
                      id="longitude"
                      :name="$tc('words.longitude')"
                      step="any"
                      maxlength="8"
                      v-model="miniGridLatLng.lon"
                      v-validate="'required|decimal:5|max:8'"
                    ></md-input>
                    <span class="md-error">
                      {{ errors.first($tc("words.longitude")) }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-40">
                  <md-button type="submit" class="md-primary set-button">
                    {{ $tc("phrases.setPoints") }}
                  </md-button>
                </div>
              </form>
            </div>

            <div class="md-layout-item md-size-40 md-small-size-100">
              <md-button class="md-primary save-button" @click="saveMiniGrid()">
                {{ $tc("words.save") }}
              </md-button>
            </div>
          </div>
          <div class="md-layout-item md-size-100 map-area">
            <MgMap
              ref="miniGridMapRef"
              :mapping-service="mappingService"
              :marker="true"
              @locationSet="miniGridLocationSet"
            />
          </div>
        </md-card-content>
        <md-progress-bar
          md-mode="indeterminate"
          class="md-progress-bar"
          v-if="loading"
        />
      </md-card>
    </widget>
    <redirection-modal
      :redirection-url="redirectionUrl"
      :imperative-item="imperativeItem"
      :dialog-active="redirectDialogActive"
    />
  </div>
</template>

<script>
import { ClusterService } from "@/services/ClusterService"
import { ICONS, MappingService } from "@/services/MappingService"
import { MiniGridService } from "@/services/MiniGridService"
import Widget from "@/shared/Widget.vue"
import RedirectionModal from "@/shared/RedirectionModal"
import MgMap from "@/modules/Map/MiniGridMap.vue"
import { notify } from "@/mixins"

export default {
  name: "AddMiniGrid",
  mixins: [notify],
  components: {
    MgMap,
    Widget,
    RedirectionModal,
  },
  data() {
    return {
      clusterService: new ClusterService(),
      mappingService: new MappingService(),
      miniGridService: new MiniGridService(),
      miniGridName: null,
      miniGridLatLng: {
        lat: null,
        lon: null,
      },
      loading: false,
      selectedClusterId: null,
      redirectionUrl: "/locations/add-cluster",
      imperativeItem: "Cluster",
      redirectDialogActive: false,
    }
  },
  created() {
    this.mappingService.setMarkerUrl(ICONS.MINI_GRID)
  },
  mounted() {
    this.getClusters()
  },
  methods: {
    async getClusters() {
      try {
        await this.clusterService.getClusters()
        if (this.clusterService.list.length) {
          this.selectedClusterId =
            this.clusterService.list[this.clusterService.list.length - 1].id
        } else {
          this.redirectDialogActive = true
        }
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async getClusterGeoData(clusterId) {
      try {
        return await this.clusterService.getClusterGeoLocation(clusterId)
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async saveMiniGrid() {
      const validator = await this.$validator.validateAll()
      if (validator && validator) {
        try {
          this.loading = true
          const miniGrid = {
            clusterId: this.selectedClusterId,
            geoData: `${this.miniGridLatLng.lat},${this.miniGridLatLng.lon}`,
            name: this.miniGridName,
          }
          await this.miniGridService.createMiniGrid(miniGrid)
          this.alertNotify("success", this.$tc("phrases.newMiniGrid", 2))
          this.loading = false
          await this.$router.replace("/locations/add-village?id=" + miniGrid.id)
        } catch (e) {
          this.loading = false
          this.alertNotify("error", e.message)
        }
      }
    },
    miniGridLocationSet(data) {
      if (!data.error) {
        this.miniGridLatLng.lat = Number(
          data.geoDataItem.coordinates.lat.toFixed(5),
        )
        this.miniGridLatLng.lon = Number(
          data.geoDataItem.coordinates.lng.toFixed(5),
        )
      } else {
        this.miniGridLatLng.lat = null
        this.miniGridLatLng.lon = null
        this.$swal({
          type: "warning",
          text: data.error,
        })
      }
    },
    setPoints() {
      const location = [this.miniGridLatLng.lat, this.miniGridLatLng.lon]
      this.$refs.miniGridMapRef.setMiniGridMarkerManually(location)
    },
  },
  watch: {
    async selectedClusterId() {
      const clusterGeoData = await this.getClusterGeoData(
        this.selectedClusterId,
      )
      this.mappingService.setCenter([clusterGeoData.lat, clusterGeoData.lon])
      this.mappingService.setGeoData(clusterGeoData)
      this.$refs.miniGridMapRef.drawCluster()
    },
  },
}
</script>

<style scoped>
.md-progress-bar {
  position: absolute;
  top: 0;
  right: 0;
  left: 0;
}

.save-button {
  background-color: #325932 !important;
  color: #fefefe !important;
  top: 0.5rem;
  float: right;
}

.set-button {
  background-color: #448aff !important;
  color: #fefefe !important;
  top: 0.5rem;
  float: left;
}

.map-area {
  z-index: 1 !important;
}
</style>
