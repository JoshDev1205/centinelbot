import firebase from 'firebase/app'
import 'firebase/database'

const config = {
  apiKey: 'AIzaSyDEgss3Myi-37Yaaa0l1I2dFp5Lg0W0uF4',
  authDomain: 'dashboard-centinelbot.firebaseapp.com',
  databaseURL: 'https://dashboard-centinelbot.firebaseio.com',
  projectId: 'dashboard-centinelbot',
  storageBucket: 'dashboard-centinelbot.appspot.com',
  messagingSenderId: '859156591350',
  appId: '1:859156591350:web:b1facbedb3cb835e'
}

if (!firebase.apps.length) {
  firebase.initializeApp(config)
}

export const DB = firebase.database()
