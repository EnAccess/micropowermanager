<template>
    <div>
        <widget
            :title="$tc('phrases.newCluster',1)"
            color="green"
        >

            <md-card class="md-layout-item md-size-100">

                <md-card-content>
                    <div class="md-layout md-gutter">
                        <div
                            class="md-layout-item md-xlarge-size-33 md-large-size-33 md-medium-size-33 md-small-size-100">
                            <md-field :class="{'md-invalid': errors.has($tc('words.name'))}">
                                <label>{{ $tc('words.name') }}</label>
                                <md-input

                                    v-model="clusterName"
                                    :name="$tc('words.name')"
                                    id="clusterName"
                                    v-validate="'required|min:3'"
                                />

                                <span class="md-error">{{ errors.first($tc('words.name')) }}</span>
                            </md-field>
                        </div>
                        <div
                            class="md-layout-item md-xlarge-size-33 md-large-size-33 md-medium-size-33 md-small-size-100">

                            <user-list @userSelected="userSelected"></user-list>
                        </div>
                        <div
                            class="md-layout-item md-xlarge-size-33 md-large-size-33 md-medium-size-33 md-small-size-100">

                            <md-button class="md-primary save-button" @click="saveCluster()">{{ $tc('words.save') }}</md-button>
                        </div>

                        <div
                            class="md-layout-item md-xlarge-size-33 md-large-size-100 md-medium-size-100 md-small-size-100">
                            <md-list>
                                <div v-if="geoDataItems.length>0">
                                    <md-subheader v-if="typed  && clusterName!==''">Search results for {{clusterName}}
                                    </md-subheader>
                                    <md-list-item

                                        style="cursor: pointer"

                                        :key="index"
                                        v-for="(geo,index) in geoDataItems"
                                        @click="locationSelected(geo)">
                                        <md-icon v-bind:class="{ 'selected-list-item': geo.selected }">location_on
                                        </md-icon>
                                        <md-icon v-if="geo.draw_type==='draw'"
                                                 v-bind:class="{ 'selected-list-item': geo.selected }">edit
                                        </md-icon>
                                        <span class="md-list-item-text">{{geo.display_name}} </span>

                                    </md-list-item>
                                </div>
                                <div v-if="geoDataItems.length<1 && typed===true && clusterName!==''">
                                    <h4 style="color:#797979;margin-left: 1rem">{{$tc('phrases.newCluster',2,{clusterName: clusterName})}}</h4>
                                </div>
                            </md-list>
                        </div>
                        <div
                            class="md-layout-item md-xlarge-size-33 md-large-size-100 md-medium-size-100 md-small-size-100 map-area">
                            <Map
                                :geoData="geoData"
                                :clusterName="clusterName"
                                :polygon="true"
                                :center="center"
                                :filtered_types=filtered_types
                                :edit="true"
                                :remove="true"

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
            <md-dialog-title>{{ $tc('phrases.namingCluster') }}</md-dialog-title>
            <md-dialog-content>

                <div class="md-layout md-gutter">
                    <div class="md-layout-item md-large-size-100 md-medium-size-100 md-small-size-100">
                        <p>
                            {{ $tc('phrases.newClusterNotify',0) }}
                        </p>

                    </div>
                    <div class="md-layout-item md-large-size-100 md-medium-size-100 md-small-size-100">
                        <md-field :class="{'md-invalid': errors.has($tc('words.name'))}">
                            <label>{{ $tc('words.name') }}</label>
                            <md-input

                                v-model="clusterName"
                                :name="$tc('words.name')"
                                v-validate="'required|min:3'"
                            />

                            <span class="md-error">{{ errors.first($tc('words.name')) }}</span>
                        </md-field>
                    </div>
                    <div class="md-layout-item md-large-size-100 md-medium-size-100 md-small-size-100">

                        <md-button class="md-primary save-button" @click="saveCluster()">{{ $tc('words.save') }}</md-button>
                    </div>
                </div>
            </md-dialog-content>

        </md-dialog>
    </div>
</template>

<script>

import UserList from './UserList'
import Map from '../../shared/Map'
import Widget from '../../shared/widget'
import { EventBus } from '@/shared/eventbus'
import { MappingService } from '@/services/MappingService'
import { ClusterService } from '@/services/ClusterService'

const debounce = require('debounce')

export default {
    name: 'AddCluster',
    components: {
        Widget,
        UserList,
        Map,
    },
    data () {
        return {
            clusterService: new ClusterService(),
            mappingService: new MappingService(),
            center: [this.$store.getters['settings/getMapSettings'].latitude,this.$store.getters['settings/getMapSettings'].longitude],
            geoData: null,
            clusterName: '',
            user: null,
            selectedLocation: null,
            geoDataItems: [],
            typed: false,
            filtered_types: { 'polygon': true },
            dialogActive: false
        }
    },
    mounted () {
        EventBus.$on('getSearchedGeoDataItems', (geoDataItems) => {
            this.typed = false
            geoDataItems.forEach((e) => {
                for (let i = this.geoDataItems.length; i--;) {
                    let item = this.geoDataItems[i]

                    if (item.lat === e.lat && item.lon === e.lon) {

                        this.geoDataItems.splice(i, 1)
                    } else if (
                        item.draw_type === 'set' &&
                            this.clusterName !== '' &&
                            !item.display_name.toLowerCase().includes(this.clusterName.toLowerCase())) {
                        this.geoDataItems.splice(i, 1)
                    }
                }
                this.geoDataItems.push(e)
            })
            this.typed = true

        })
        EventBus.$on('getDrawedLocation', (geoDataItem) => {
            geoDataItem.display_name = this.clusterName === '' ? 'Unnamed' : this.clusterName
            this.typed = false
            this.geoDataItems.forEach((e) => {
                let item = e
                if (item.selected) {
                    item.selected = false
                }
            })
            this.geoDataItems.push(geoDataItem)

        })
        EventBus.$on('getDeletedGeoDataItems', (deletedItemIds) => {
            deletedItemIds.forEach((id) => {
                for (let i = this.geoDataItems.length; i--;) {
                    let deletedGeoDataItem = this.geoDataItems[i]
                    if (deletedGeoDataItem.leaflet_id === id) this.geoDataItems.splice(i, 1)
                }
            })
            if (this.geoDataItems.length === 0) {
                this.clusterName = ''
            }
        })
        EventBus.$on('getEditedGeoDataItems', (editedItems) => {
            editedItems.forEach((e) => {
                let editedGeoDataItem = this.geoDataItems.filter(x => x.leaflet_id === e.leaflet_id)[0]

                if (editedGeoDataItem !== undefined) {

                    editedGeoDataItem.geojson.coordinates = e.geojson.coordinates
                    editedGeoDataItem.lat = e.lat
                    editedGeoDataItem.lon = e.lon
                }

            })

        })

    },
    methods: {
        async getClusterGeoData () {
            this.geoData = await this.mappingService.getSearchResult(this.clusterName, this.filtered_types)
        },
        async locationSelected (location) {
            this.geoDataItems.forEach((e) => {
                e.selected = false
            })

            location.selected = true
            this.selectedLocation = location
            this.geoData = this.mappingService.focusLocation(this.selectedLocation)

        },
        async saveCluster () {
            this.dialogActive = false
            this.selectedLocation = this.geoDataItems.filter(x => x.selected === true)[0]

            if (this.selectedLocation === null || this.selectedLocation === undefined) {
                this.$swal({
                    type: 'error',
                    title: this.$tc('phrases.newClusterNotify',1),
                    text: this.$tc('phrases.newClusterNotify',2),
                })
                return
            }
            if (this.user === null) {
                this.$swal({
                    type: 'error',
                    title:  this.$tc('phrases.newClusterNotify2',0),
                    text:  this.$tc('phrases.newClusterNotify2',1),
                })
                return
            }
            if (this.clusterName === 'Unnamed' || this.clusterName === '') {
                this.dialogActive = true
                return
            }
            try {
                await this.clusterService.createCluster(this.selectedLocation.type, this.selectedLocation, this.clusterName, this.user)
                this.alertNotify('success',  this.$tc('phrases.newClusterNotify2',2))
                await this.$router.replace('/clusters')
            } catch (e) {
                this.alertNotify('error', e.message)
            }

        },
        userSelected (user) {
            this.user = user
        },
        alertNotify (type, message) {
            this.$notify({
                group: 'notify',
                type: type,
                title: type + ' !',
                text: message
            })
        },
    },

    watch: {
        clusterName: debounce(function () {
            if (this.clusterName.length > 2) {
                let selectedCluster = this.geoDataItems.filter(x => x.selected === true)[0]
                if (selectedCluster !== undefined && selectedCluster.display_name === '') {
                    selectedCluster.display_name = this.clusterName
                } else {
                    this.typed = true
                    this.getClusterGeoData()
                }

            }

        }, 1000)
    }
}
</script>

<style scoped>
    .map-area {
        z-index: 1 !important
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

    cluster-input {
        color: #747474 !important
    }
</style>
