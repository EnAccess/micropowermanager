<template>
  <div>
    <widget
      :id="'village-list'"
      :title="$tc('words.village', 2)"
      :button="true"
      :button-text="$tc('phrases.addVillage')"
      color="primary"
      @widgetAction="goToAddVillage"
    >
      <md-table
        style="width: 100%"
        v-model="cities"
        md-card
        md-fixed-header
        v-if="cities.length"
      >
        <md-table-row slot="md-table-row" slot-scope="{ item }">
          <md-table-cell :md-label="$tc('words.name')">
            {{ item.name }}
          </md-table-cell>
          <md-table-cell :md-label="$tc('words.miniGrid')">
            {{ item.mini_grid ? item.mini_grid.name : "-" }}
          </md-table-cell>
          <md-table-cell :md-label="$tc('words.cluster')">
            {{
              item.mini_grid && item.mini_grid.cluster
                ? item.mini_grid.cluster.name
                : "-"
            }}
          </md-table-cell>
          <md-table-cell :md-label="$tc('words.country')">
            {{ item.country ? item.country.country_name : "-" }}
          </md-table-cell>
          <md-table-cell md-label="">
            <md-button
              class="md-icon-button md-dense"
              @click="openEditDialog(item)"
            >
              <md-icon>edit</md-icon>
            </md-button>
            <md-button
              class="md-icon-button md-dense md-accent"
              @click="confirmDelete(item)"
            >
              <md-icon>delete</md-icon>
            </md-button>
          </md-table-cell>
        </md-table-row>
      </md-table>
      <div v-else class="empty-state">
        {{ $tc("phrases.noRecords") }}
      </div>
      <md-progress-bar md-mode="indeterminate" v-if="loading" />
    </widget>

    <md-dialog
      class="village-edit-dialog"
      :md-active.sync="editDialogActive"
      :md-close-on-esc="true"
      :md-click-outside-to-close="true"
    >
      <md-dialog-title>{{ $tc("phrases.editVillage") }}</md-dialog-title>
      <md-dialog-content>
        <div class="md-layout md-gutter">
          <div class="md-layout-item md-size-33 md-small-size-100">
            <md-field>
              <label>{{ $tc("words.name") }}</label>
              <md-input v-model="editName" />
            </md-field>
          </div>
          <div class="md-layout-item md-size-33 md-small-size-100">
            <md-field>
              <label for="editMiniGrid">{{ $tc("words.miniGrid") }}</label>
              <md-select
                v-model="editMiniGridId"
                name="editMiniGrid"
                id="editMiniGrid"
                @md-selected="onEditMiniGridSelected"
              >
                <md-option
                  v-for="miniGrid in miniGrids"
                  :value="miniGrid.id"
                  :key="miniGrid.id"
                >
                  {{ miniGrid.name }}
                  <span v-if="miniGrid.cluster">
                    ({{ miniGrid.cluster.name }})
                  </span>
                </md-option>
              </md-select>
            </md-field>
          </div>
          <div class="md-layout-item md-size-33 md-small-size-100">
            <md-field>
              <label for="editCountry">{{ $tc("words.country") }}</label>
              <md-select
                v-model="editCountryId"
                name="editCountry"
                id="editCountry"
              >
                <md-option
                  v-for="country in countries"
                  :value="country.id"
                  :key="country.id"
                >
                  {{ country.country_name }}
                </md-option>
              </md-select>
            </md-field>
          </div>
          <div class="md-layout-item md-size-35 md-small-size-100">
            <md-field>
              <label>{{ $tc("words.latitude") }}</label>
              <md-input v-model="cityLatLng.lat" step="any" maxlength="8" />
            </md-field>
          </div>
          <div class="md-layout-item md-size-35 md-small-size-100">
            <md-field>
              <label>{{ $tc("words.longitude") }}</label>
              <md-input v-model="cityLatLng.lon" step="any" maxlength="8" />
            </md-field>
          </div>
          <div class="md-layout-item md-size-30 md-small-size-100">
            <md-button class="md-primary" @click="setPoints">
              {{ $tc("phrases.setPoints") }}
            </md-button>
          </div>
        </div>
        <div class="map-area">
          <village-map
            v-if="editDialogActive"
            ref="villageMapRef"
            :mapping-service="mappingService"
            :marker="true"
            @locationSet="villageLocationSet"
          />
        </div>
      </md-dialog-content>
      <md-dialog-actions>
        <md-button @click="editDialogActive = false">
          {{ $tc("words.cancel") }}
        </md-button>
        <md-button class="md-primary" @click="saveEdit">
          {{ $tc("words.save") }}
        </md-button>
      </md-dialog-actions>
    </md-dialog>

    <md-dialog
      class="village-blockers-dialog"
      :md-active.sync="blockersDialogActive"
      :md-close-on-esc="true"
      :md-click-outside-to-close="true"
    >
      <md-dialog-title>{{ $tc("phrases.linkedAddresses") }}</md-dialog-title>
      <md-dialog-content>
        <p>
          {{
            $tc("phrases.linkedAddressesExplanation", 0, {
              name: blockedCity ? blockedCity.name : "",
            })
          }}
        </p>
        <md-table v-if="linkedAddresses.length">
          <md-table-row>
            <md-table-head>{{ $tc("words.name") }}</md-table-head>
            <md-table-head>{{ $tc("words.type") }}</md-table-head>
            <md-table-head>{{ $tc("words.phone") }}</md-table-head>
            <md-table-head>{{ $tc("words.address") }}</md-table-head>
          </md-table-row>
          <md-table-row
            v-for="address in linkedAddresses"
            :key="address.id"
            :class="{ 'former-address': !address.is_primary }"
          >
            <md-table-cell>{{ address.owner_name || "-" }}</md-table-cell>
            <md-table-cell>{{ address.owner_type }}</md-table-cell>
            <md-table-cell>{{ address.phone || "-" }}</md-table-cell>
            <md-table-cell>
              {{
                address.is_primary
                  ? $tc("phrases.currentAddress")
                  : $tc("phrases.formerAddress")
              }}
            </md-table-cell>
          </md-table-row>
        </md-table>
        <md-field>
          <label for="reassignCity">{{ $tc("phrases.moveAddressesTo") }}</label>
          <md-select
            v-model="reassignCityId"
            name="reassignCity"
            id="reassignCity"
          >
            <md-option
              v-for="city in reassignTargets"
              :value="city.id"
              :key="city.id"
            >
              {{ city.name }}
            </md-option>
          </md-select>
        </md-field>
      </md-dialog-content>
      <md-dialog-actions>
        <md-button @click="blockersDialogActive = false">
          {{ $tc("words.cancel") }}
        </md-button>
        <md-button
          class="md-accent"
          :disabled="!reassignCityId"
          @click="moveAddressesAndDelete"
        >
          {{ $tc("phrases.moveAddressesAndDelete") }}
        </md-button>
      </md-dialog-actions>
    </md-dialog>
  </div>
</template>

<script>
import { geoJsonToLatLon, latLonToGeoJsonPoint } from "@/Helpers/Utils.js"
import { notify } from "@/mixins/notify.js"
import { villageMapContext } from "@/mixins/villageMapContext.js"
import VillageMap from "@/modules/Map/VillageMap.vue"
import { CityService } from "@/services/CityService.js"
import { ClusterService } from "@/services/ClusterService.js"
import CountryService from "@/services/CountryService.js"
import { ICONS, MappingService } from "@/services/MappingService.js"
import { MiniGridService } from "@/services/MiniGridService.js"
import Widget from "@/shared/Widget.vue"

export default {
  name: "VillageList",
  mixins: [notify, villageMapContext],
  components: {
    VillageMap,
    Widget,
  },
  data() {
    return {
      cityService: new CityService(),
      clusterService: new ClusterService(),
      countryService: new CountryService(),
      miniGridService: new MiniGridService(),
      mappingService: new MappingService(),
      cities: [],
      countries: [],
      miniGrids: [],
      loading: false,
      editDialogActive: false,
      editingCity: null,
      editName: "",
      editCountryId: null,
      editMiniGridId: null,
      blockersDialogActive: false,
      blockedCity: null,
      linkedAddresses: [],
      reassignCityId: null,
    }
  },
  computed: {
    reassignTargets() {
      if (!this.blockedCity) return []
      return this.cities.filter((city) => city.id !== this.blockedCity.id)
    },
  },
  created() {
    this.mappingService.setConstantMarkerUrl(ICONS.MINI_GRID)
    this.mappingService.setMarkerUrl(ICONS.VILLAGE)
  },
  mounted() {
    this.loadCities()
    this.loadCountries()
    this.loadMiniGrids()
  },
  methods: {
    async loadCities() {
      this.loading = true
      try {
        const cities = await this.cityService.getCities()
        this.cities = Array.isArray(cities) ? cities : []
      } catch (e) {
        this.alertNotify("error", e.message)
      }
      this.loading = false
    },
    async loadCountries() {
      try {
        await this.countryService.getCountries()
        this.countries = this.countryService.list
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async loadMiniGrids() {
      try {
        const miniGrids = await this.miniGridService.getMiniGrids()
        this.miniGrids = Array.isArray(miniGrids) ? miniGrids : []
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    goToAddVillage() {
      this.$router.push("/locations/add-village")
    },
    async openEditDialog(city) {
      this.editingCity = city
      this.editName = city.name || ""
      this.editCountryId = city.country_id || null
      this.editMiniGridId = city.mini_grid_id || null
      const location = geoJsonToLatLon(city.location)
      this.cityLatLng.lat = location ? location.lat : null
      this.cityLatLng.lon = location ? location.lon : null
      this.editDialogActive = true

      await this.drawVillageMap()
    },
    onEditMiniGridSelected() {
      this.drawVillageMap()
    },
    async drawVillageMap() {
      try {
        const miniGridWithGeoData = await this.loadVillageMapContext(
          this.editMiniGridId,
        )
        if (!miniGridWithGeoData) return
        await this.$nextTick()
        const villageMap = this.$refs.villageMapRef
        if (!villageMap) return
        villageMap.map.invalidateSize()
        villageMap.drawCluster()
        villageMap.setMiniGridMarker()
        this.placeVillageMarkerWithinCluster(villageMap)
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    placeVillageMarkerWithinCluster(villageMap) {
      if (this.cityLatLng.lat === null || this.cityLatLng.lon === null) return

      const location = [this.cityLatLng.lat, this.cityLatLng.lon]

      if (villageMap.isWithinCluster(location)) {
        villageMap.setVillageMarkerManually(location)
        return
      }

      // The village sits outside the cluster of the mini-grid just picked, so it has
      // to be positioned again before the move can be saved.
      villageMap.removeExistingMarkers()
      this.cityLatLng.lat = null
      this.cityLatLng.lon = null
      this.alertNotify("warning", this.$tc("phrases.positionVillageInCluster"))
    },
    async saveEdit() {
      if (!this.editName || !this.editName.trim()) {
        this.alertNotify("error", this.$tc("phrases.nameRequired"))
        return
      }
      try {
        const cityData = {
          name: this.editName.trim(),
          miniGridId: this.editMiniGridId,
          countryId: this.editCountryId,
        }
        if (this.cityLatLng.lat !== null && this.cityLatLng.lon !== null) {
          cityData.geoJson = latLonToGeoJsonPoint(
            this.cityLatLng.lat,
            this.cityLatLng.lon,
          )
        }
        await this.cityService.updateCity(this.editingCity.id, cityData)
        this.editDialogActive = false
        this.alertNotify("success", this.$tc("phrases.villageUpdated"))
        await this.loadCities()
      } catch (e) {
        this.alertNotify("error", e.message || this.$tc("phrases.updateFailed"))
      }
    },
    confirmDelete(city) {
      this.$swal({
        type: "question",
        title: this.$tc("phrases.deleteVillage"),
        text: this.$tc("phrases.deleteVillageNotify", 0, { name: city.name }),
        width: "35%",
        confirmButtonText: this.$tc("words.confirm"),
        showCancelButton: true,
        cancelButtonText: this.$tc("words.cancel"),
        focusCancel: true,
      }).then((result) => {
        if (result.value) {
          this.deleteVillage(city)
        }
      })
    },
    async deleteVillage(city, options = {}) {
      try {
        await this.cityService.deleteCity(city.id, options)
        this.blockersDialogActive = false
        this.alertNotify("success", this.$tc("phrases.villageDeleted"))
        await this.loadCities()
      } catch (e) {
        this.alertNotify("error", e.message || this.$tc("phrases.deleteFailed"))
        await this.openBlockersDialog(city)
      }
    },
    async openBlockersDialog(city) {
      try {
        const addresses = await this.cityService.getLinkedAddresses(city.id)
        if (!Array.isArray(addresses) || !addresses.length) return
        this.blockedCity = city
        this.linkedAddresses = addresses
        this.reassignCityId = null
        this.blockersDialogActive = true
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    moveAddressesAndDelete() {
      this.deleteVillage(this.blockedCity, {
        reassignAddressesTo: this.reassignCityId,
      })
    },
  },
}
</script>

<style lang="scss" scoped>
.empty-state {
  padding: 2rem;
  text-align: center;
  color: #777;
}

.village-edit-dialog,
.village-blockers-dialog {
  ::v-deep .md-dialog-container {
    width: 70%;
    max-width: 900px;
  }
}

.map-area {
  z-index: 1 !important;
}

.former-address {
  color: #777;
  font-style: italic;
}
</style>
