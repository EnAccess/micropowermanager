<template>
    <div>
        <widget
            :title="$tc('phrases.newMiniGrid')"
            color="green"
            >
            <md-card>
                <md-card-content>
                    <div class="md-layout md-gutter md-size-100">
                        <div class="md-layout-item md-size-70 md-small-size-100">
                            <md-field :class="{'md-invalid': errors.has($tc('words.name'))}">
                                <label for="miniGrid_name">{{$tc('words.name')}}</label>
                                <md-input
                                    id="miniGridName"
                                    :name="$tc('words.name')"
                                    v-model="miniGridName"
                                    v-validate="'required|min:3'"></md-input>
                                <span class="md-error">{{errors.first($tc('words.name'))}}</span>
                            </md-field>

                        </div>
                        <div class="md-layout-item md-size-30 md-small-size-100">
                            <md-field :class="{'md-invalid': errors.has($tc('words.cluster'))}">
                                <label for="clusterName">{{$tc('words.cluster')}}</label>
                                <md-select
                                    v-model="selectedClusterId"
                                    :name="$tc('words.cluster')"
                                    id="clusterName"
                                    v-validate="'required'"
                                    @md-selected="selectCluster">
                                    <md-option v-for="(cluster) in clusters" :value="cluster.id"
                                               :key="cluster.id">
                                        {{cluster.name}}
                                    </md-option>
                                </md-select>
                                <span class="md-error">{{errors.first($tc('words.cluster'))}}</span>
                            </md-field>
                        </div>
                    </div>
                    <div class="md-layout md-gutter md-size-100">
                        <div class="md-layout md-gutter md-size-60 md-small-size-100" style="padding-left: 1.5rem !important;">
                            <form class="md-layout md-gutter" @submit.prevent="validatePoints"
                                   style="padding-left: 1.5rem !important;"
                            >

                                <div class="md-layout-item md-size-30 md-small-size-100">
                                    <md-field :class="{'md-invalid': errors.has($tc('words.latitude'))}">
                                        <label for="latitude">{{$tc('words.latitude')}}</label>
                                        <md-input
                                            id="latitude"
                                            :name="$tc('words.latitude')"
                                            maxlength="8"
                                            step="any"
                                            v-model="miniGridLatLng.lat"
                                            v-validate="'required|decimal:5|max:8'"></md-input>
                                        <span class="md-error">{{errors.first($tc('words.latitude'))}}</span>
                                    </md-field>
                                </div>
                                <div class="md-layout-item md-size-30 md-small-size-100">
                                    <md-field :class="{'md-invalid': errors.has($tc('words.longitude'))}">
                                        <label for="longitude">{{$tc('words.longitude')}}</label>
                                        <md-input
                                            id="longitude"
                                            :name="$tc('words.longitude')"

                                            step="any"
                                            maxlength="8"
                                            v-model="miniGridLatLng.lon"
                                            v-validate="'required|decimal:5|max:8'"></md-input>
                                        <span class="md-error">{{errors.first($tc('words.longitude'))}}</span>
                                    </md-field>
                                </div>
                                <div class="md-layout-item md-size-40">
                                    <md-button type="submit" class="md-primary set-button">{{$tc('phrases.setPoints')}}</md-button>

                                </div>
                            </form>

                        </div>

                        <div class="md-layout-item md-size-40 md-small-size-100">
                            <md-button class="md-primary save-button" @click="saveMiniGrid()">{{$tc('words.save')}}</md-button>

                        </div>
                    </div>
                    <div class="md-layout-item md-size-100 map-area">
                        <Map
                            :geoData="geoData"
                            :clusterName="clusterName"
                            :marker="true"
                            :markerCount="1"
                            :markerLocations="markerLocations"
                            :remove="true"
                            :center="center"
                            :markerUrl="markerUrl"
                        />
                    </div>
                </md-card-content>
                <md-progress-bar md-mode="indeterminate" class="md-progress-bar" v-if="loading"/>

            </md-card>
        </widget>
        <redirection-modal :redirection-url="redirectionUrl" :imperative-item="imperativeItem"
                           :dialog-active="redirectDialogActive"/>
    </div>
</template>

<script>
import marker from 'leaflet/dist/images/marker-icon.png'
import Widget from '../../shared/widget'
import { ClusterService } from '../../services/ClusterService'
import Map from '../../shared/Map'
import { MappingService } from '../../services/MappingService'
import { EventBus } from '../../shared/eventbus'
import { MiniGridService } from '../../services/MiniGridService'
import RedirectionModal from '../../shared/RedirectionModal'

export default {
    name: 'AddMiniGrid',
    components: {
        Widget,
        Map,
        RedirectionModal
    },
    data () {
        return {
            clusterService: new ClusterService(),
            mappingService: new MappingService(),
            miniGridService: new MiniGridService(),
            clusterId: null,
            geoData: null,
            center: [this.$store.getters['settings/getMapSettings'].latitude,this.$store.getters['settings/getMapSettings'].longitude],
            marker: true,
            markerUrl: marker,
            miniGridName: '',
            miniGridLatLng: {
                lat: null,
                lon: null
            },
            loading: false,
            clusterName: '',
            clusters: [],
            selectedClusterId: '',
            miniGridId: null,
            markerLocations: [],
            redirectionUrl: '/locations/add-cluster',
            imperativeItem: 'Cluster',
            redirectDialogActive: false
        }
    },
    mounted () {
        this.getClusters()
        EventBus.$on('getDrawedMarker', (geoDataItem) => {
            this.miniGridLatLng.lat = Number(geoDataItem.geojson.coordinates.lat.toFixed(5))
            this.miniGridLatLng.lon = Number(geoDataItem.geojson.coordinates.lng.toFixed(5))
        })
        EventBus.$on('markerError', (message) => {
            this.$swal({
                type: 'warn',
                text: message,
            })

        })
    },
    methods: {
        async getClusters () {
            try {
                this.clusters = await this.clusterService.getClusters()
                if (this.clusters.length > 0) {
                    this.selectedClusterId = this.clusters[this.clusters.length - 1].id
                } else {
                    this.redirectDialogActive = true
                }
            } catch (e) {
                this.alertNotify('error', e.message)
            }
        },
        async getGeoData (clusterId) {
            try {
                this.clusterId = clusterId
                let clusterGeoData = await this.clusterService.getClusterGeoLocation(clusterId)

                this.center = [clusterGeoData.lat, clusterGeoData.lon]
                this.geoData = this.mappingService.focusLocation(clusterGeoData)
            } catch (e) {
                this.alertNotify('error', e.message)
            }
        },
        async validatePoints () {
            let validator = await this.$validator.validateAll('Points-Form')
            if (validator) {
                this.setMarker()
            }

        },
        async saveMiniGrid () {

            let validator = await this.$validator.validateAll()
            let validatorPoints = await this.$validator.validateAll('Points-Form')
            if (validator && validatorPoints) {
                try {
                    this.loading = true
                    const miniGrid = await this.miniGridService.createMiniGrid(this.miniGridName, this.clusterId, this.miniGridLatLng)
                    this.alertNotify('success', this.$tc('phrases.newMiniGrid',2))
                    this.loading = false
                    await this.$router.replace('/dashboards/mini-grid/' + miniGrid.id)
                } catch (e) {
                    this.loading = false
                    this.alertNotify('error', e.message)
                }
            }
        },
        selectCluster (cluster) {
            this.miniGridLatLng = {
                lat: null,
                lon: null
            }
            this.miniGridName = ''
            this.getGeoData(cluster)
        },
        setMarker () {
            this.markerLocations = []
            this.markerLocations.push([this.miniGridLatLng.lat, this.miniGridLatLng.lon])
        },
        alertNotify (type, message) {
            this.$notify({
                group: 'notify',
                type: type,
                title: type + ' !',
                text: message
            })
        },
    }
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
        z-index: 1 !important
    }

</style>
