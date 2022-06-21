import PaginatorRepository from './PaginatorRepository'
import CredentialRepository from './CredentialRepository'
import SiteRepository from './SiteRepository'
import CustomerRepository from './CustomerRepository'
import MeterRepository from './MeterRepository'
import AgentRepository from './AgentRepository'
import SettingRepository from './SettingRepository'
import SmsSettingRepository from './SmsSettingRepository'
import SyncSettingRepository from './SyncSettingRepository'
import SmsBodiesRepository from './SmsBodiesRepository'
import SmsVariableDefaultValueRepository from './SmsVariableDefaultValueRepository'
import FeedbackWordRepository from './FeedbackWordRepository'

const repositories = {
    'paginate': PaginatorRepository,
    'credential': CredentialRepository,
    'site': SiteRepository,
    'customer': CustomerRepository,
    'meter': MeterRepository,
    'agent': AgentRepository,
    'setting': SettingRepository,
    'smsSetting': SmsSettingRepository,
    'syncSetting': SyncSettingRepository,
    'smsBodies':SmsBodiesRepository,
    'smsVariableDefaultValue':SmsVariableDefaultValueRepository,
    'feedBackWord':FeedbackWordRepository
}
export default {
    get: name => repositories[name]
}