<template>
    <div class="page-container">
      <add-customer/>

        <widget
            :id="'client-list-widget'"
            :title="$tc('phrases.customerList')"
            :search="true"
            :subscriber="subscriber"
            :button="true"
            @widgetAction="showAddCustomer"
            :button-text="'Add customer'"
            :paginator="people.paginator"
            :route_name="'/people'"
            color="green"
        >

                <md-table md-card style="margin-left: 0">
                    <md-table-row>
                        <md-table-head>{{ $tc('words.id') }}</md-table-head>
                        <md-table-head>{{ $tc('words.name') }}</md-table-head>
                        <md-table-head>{{ $tc('words.phone') }}</md-table-head>
                        <md-table-head>{{ $tc('words.city') }}</md-table-head>
                        <md-table-head>{{ $tc('words.meter') }}</md-table-head>
                        <md-table-head>{{ $tc('phrases.lastUpdate') }}</md-table-head>
                    </md-table-row>


                    <md-table-row v-for="client in people.list" :key="client.id" @click="detail(client.id)"
                                  style="cursor:pointer;">
                        <md-table-cell> {{ client.id}}</md-table-cell>
                        <md-table-cell> {{ client.name}} {{client.surname}}</md-table-cell>
                        <md-table-cell v-if="client.addresses.length>0"> {{ client.addresses[0].phone}}
                        </md-table-cell>
                        <md-table-cell class="hidden-xs" v-if="client.addresses.length>0"> {{
                                client.addresses[0].city ?
                                    client.addresses[0].city.name: '-'}}
                        </md-table-cell>
                        <md-table-cell v-if="client.meters.length>0">
                            {{meterList(client.meters)}}
                        </md-table-cell>
                        <md-table-cell v-if="client.meters.length===0">
                            -
                        </md-table-cell>
                        <md-table-cell class="hidden-xs"> {{ dateForHumans( client.lastUpdate) }}</md-table-cell>
                    </md-table-row>

                </md-table>

        </widget>


    </div>
</template>
<script>

import { resources } from '@/resources'
import { Paginator } from '@/classes/paginator'
import { EventBus } from '@/shared/eventbus'
import Widget from '../../shared/widget'
import { People } from '@/classes/people'
import moment from 'moment'
import AddCustomer from '@/components/Client/AddCustomer'
const debounce = require('debounce')

export default {
    name: 'ClientList',
    components: {AddCustomer, Widget },
    data () {
        return {
            subscriber: 'client.list',
            people: new People(),
            clientList: null,
            tmpClientList: null,
            paginator: new Paginator(resources.person.list),
            searchTerm: '',
            currentFrom: 0,
            currentTo: 0,
            total: 0,
            currentPage: 0,
            totalPages: 0,
        }
    },

    watch: {
        searchTerm: debounce(function () {
            if (this.searchTerm.length > 0) {
                this.doSearch(this.searchTerm)
            } else {
                this.showAllEntries()

            }

        }, 1000),

    },

    mounted () {

        this.getClientList()
        EventBus.$on('pageLoaded', this.reloadList)
        EventBus.$on('searching', this.searching)
        EventBus.$on('end_searching', this.endSearching)

    },
    beforeDestroy () {
        EventBus.$off('pageLoaded', this.reloadList)
        EventBus.$off('searching', this.searching)
        EventBus.$off('end_searching', this.endSearching)
    },

    methods: {
        showAddCustomer() {
          console.log("show add customer ");
            EventBus.$emit('addNewCustomer')
        },
        reloadList (subscriber, data) {
            if (subscriber !== this.subscriber){
                return
            }
            this.people.updateList(data)
            EventBus.$emit('widgetContentLoaded',this.subscriber, this.people.list.length)
        },
        searching (searchTerm) {
            this.people.search(searchTerm)
        },
        endSearching () {
            this.people.showAll()
        },

        detail (id) {
            this.$router.push({ path: '/people/' + id })
        },
        dateForHumans (date) {
            return moment(date, 'YYYY-MM-DD HH:mm:ss').fromNow()
        },

        getClientList (pageNumber = 1) {

            this.paginator.loadPage(pageNumber, this.searching ? { 'term': this.searchTerm } : {}).then(response => {
                this.tmpClientList = this.clientList = response.data
            })
        },

        meterList (meters) {
            let stringified = ''
            for (let i = 0; i < meters.length; i++) {
                if (meters[i].meter === null || meters[i].meter === 'null') {
                    continue
                }
                stringified += meters[i].meter.serial_number
                if (i !== meters.length - 1) {
                    stringified += ', '
                }
            }
            return stringified
        },

        doSearch (searchTerm) {
            this.searching = true

            this.paginator = new Paginator(resources.person.search)

            this.paginator.loadPage(1, { 'term': searchTerm })
                .then(response => {
                    this.clientList = response.data
                })

        },
        showAllEntries () {
            this.searchTerm = ''
            this.paginator = new Paginator(resources.person.list)
            this.searching = false
            this.currentPage = 0
            this.getClientList()

        },
        clearSearch () {
            this.searchTerm = ''
        },

    }

}
</script>


<style lang="scss" scoped>
    .md-app {
        min-height: 100vh;
        border: 1px solid rgba(#000, .12);
    }

    // Demo purposes only
    .md-drawer {
        width: 230px;
        max-width: calc(100vw - 125px);
    }

</style>
