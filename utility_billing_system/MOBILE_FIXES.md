# 🔧 Mobile App Logout Fix

## Issue
After logout confirmation, app navigates back to MeterInput instead of LoginScreen.

## Solution

### 1. Update Your Logout Function

Replace your current logout implementation with this:

```javascript
// In your MeterInput screen or wherever you have the logout button

import { Alert } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';

const SESSION_KEY = "@utility_reader_session";

const handleLogout = () => {
  Alert.alert(
    "Confirm Logout",
    "Are you sure you want to logout?",
    [
      {
        text: "Cancel",
        style: "cancel"
      },
      {
        text: "Logout",
        style: "destructive",
        onPress: async () => {
          try {
            // Clear session data
            await AsyncStorage.removeItem(SESSION_KEY);
            
            // Clear any other cached data if needed
            await AsyncStorage.removeItem('@meters_cache');
            await AsyncStorage.removeItem('@pending_readings');
            
            // Navigate to login and reset navigation stack
            navigation.reset({
              index: 0,
              routes: [{ name: 'Login' }], // or 'LoginScreen' - use your exact screen name
            });
          } catch (error) {
            console.error('Logout error:', error);
            Alert.alert('Error', 'Failed to logout. Please try again.');
          }
        }
      }
    ]
  );
};
```

### 2. Create a Dedicated Logout Component (Optional but Recommended)

Create a new file: `components/LogoutButton.js`

```javascript
import React from 'react';
import { Alert } from 'react-native';
import { Button } from 'react-native-paper';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { useNavigation } from '@react-navigation/native';

const SESSION_KEY = "@utility_reader_session";

const LogoutButton = ({ style, mode = "contained" }) => {
  const navigation = useNavigation();

  const handleLogout = () => {
    Alert.alert(
      "Confirm Logout",
      "Are you sure you want to logout?",
      [
        {
          text: "Cancel",
          style: "cancel"
        },
        {
          text: "Logout",
          style: "destructive",
          onPress: async () => {
            try {
              // Clear all session data
              await AsyncStorage.multiRemove([
                SESSION_KEY,
                '@meters_cache',
                '@pending_readings',
                '@offline_data'
              ]);
              
              console.log('Session cleared, navigating to login...');
              
              // Reset navigation stack to login screen
              navigation.reset({
                index: 0,
                routes: [{ name: 'Login' }],
              });
            } catch (error) {
              console.error('Logout error:', error);
              Alert.alert('Error', 'Failed to logout. Please try again.');
            }
          }
        }
      ]
    );
  };

  return (
    <Button
      mode={mode}
      onPress={handleLogout}
      style={style}
      icon="logout"
      textColor={mode === "contained" ? "#fff" : "#dc3545"}
      buttonColor={mode === "contained" ? "#dc3545" : undefined}
    >
      Logout
    </Button>
  );
};

export default LogoutButton;
```

### 3. Usage in Your Screen

```javascript
import LogoutButton from './components/LogoutButton';

// In your MeterInput or Dashboard screen
const MeterInputScreen = ({ navigation, route }) => {
  return (
    <View style={styles.container}>
      {/* Your other components */}
      
      {/* Add logout button */}
      <LogoutButton style={styles.logoutButton} />
    </View>
  );
};
```

### 4. Add Logout to Navigation Header (Better UX)

```javascript
import React, { useLayoutEffect } from 'react';
import { TouchableOpacity, Alert } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import AsyncStorage from '@react-native-async-storage/async-storage';

const SESSION_KEY = "@utility_reader_session";

const MeterInputScreen = ({ navigation, route }) => {
  
  useLayoutEffect(() => {
    navigation.setOptions({
      headerRight: () => (
        <TouchableOpacity
          onPress={handleLogout}
          style={{ marginRight: 15 }}
        >
          <Ionicons name="log-out-outline" size={24} color="#fff" />
        </TouchableOpacity>
      ),
    });
  }, [navigation]);

  const handleLogout = () => {
    Alert.alert(
      "Confirm Logout",
      "Are you sure you want to logout?",
      [
        {
          text: "Cancel",
          style: "cancel"
        },
        {
          text: "Logout",
          style: "destructive",
          onPress: async () => {
            try {
              await AsyncStorage.removeItem(SESSION_KEY);
              
              // IMPORTANT: Use reset to clear navigation stack
              navigation.reset({
                index: 0,
                routes: [{ name: 'Login' }],
              });
            } catch (error) {
              console.error('Logout error:', error);
            }
          }
        }
      ]
    );
  };

  return (
    <View style={styles.container}>
      {/* Your screen content */}
    </View>
  );
};
```

### 5. Check Your Navigation Setup

Make sure your navigator is set up correctly:

```javascript
// App.js or your navigation file
import { NavigationContainer } from '@react-navigation/native';
import { createStackNavigator } from '@react-navigation/stack';

const Stack = createStackNavigator();

function App() {
  return (
    <NavigationContainer>
      <Stack.Navigator 
        initialRouteName="Login"
        screenOptions={{
          headerStyle: {
            backgroundColor: '#0d6efd',
          },
          headerTintColor: '#fff',
          headerTitleStyle: {
            fontWeight: 'bold',
          },
        }}
      >
        <Stack.Screen 
          name="Login" 
          component={LoginScreen}
          options={{ headerShown: false }}
        />
        <Stack.Screen 
          name="MeterInput" 
          component={MeterInputScreen}
          options={{ 
            title: 'Meter Reading',
            headerLeft: null, // Disable back button
          }}
        />
      </Stack.Navigator>
    </NavigationContainer>
  );
}
```

### 6. Update LoginScreen Session Check

Make sure your LoginScreen properly checks for session:

```javascript
// In LoginScreen.js
useEffect(() => {
  const checkSession = async () => {
    try {
      const session = await AsyncStorage.getItem(SESSION_KEY);
      if (session) {
        const userData = JSON.parse(session);
        if (userData?.ReaderID) {
          // User already logged in
          navigation.replace("MeterInput", { user: userData });
          return;
        }
      }
    } catch (error) {
      console.error("Session check error:", error);
      // Clear corrupted session
      await AsyncStorage.removeItem(SESSION_KEY);
    } finally {
      setIsCheckingSession(false);
    }
  };

  checkSession();
}, []);
```

## ✅ Complete Logout Flow

1. User clicks logout button
2. Confirmation alert appears
3. User confirms logout
4. Clear AsyncStorage session data
5. Use `navigation.reset()` to clear navigation stack
6. Navigate to Login screen
7. Login screen shows (no automatic redirect)

## 🔍 Debugging

If logout still doesn't work, add console logs:

```javascript
const handleLogout = async () => {
  console.log('1. Logout initiated');
  
  try {
    await AsyncStorage.removeItem(SESSION_KEY);
    console.log('2. Session cleared');
    
    const check = await AsyncStorage.getItem(SESSION_KEY);
    console.log('3. Session after clear:', check); // Should be null
    
    console.log('4. Navigating to Login...');
    navigation.reset({
      index: 0,
      routes: [{ name: 'Login' }],
    });
    console.log('5. Navigation complete');
  } catch (error) {
    console.error('Logout error:', error);
  }
};
```

## 🎯 Key Points

✅ Use `navigation.reset()` instead of `navigation.navigate()`
✅ Clear AsyncStorage session data
✅ Set `index: 0` to prevent back navigation
✅ Use exact screen name from your navigator
✅ Disable back button on authenticated screens with `headerLeft: null`

## 📝 Screen Name Reference

Make sure you're using the correct screen name. Check your navigator:

```javascript
// If your screen is defined as:
<Stack.Screen name="Login" component={LoginScreen} />

// Then use:
navigation.reset({
  index: 0,
  routes: [{ name: 'Login' }], // ✅ Correct
});

// NOT:
routes: [{ name: 'LoginScreen' }], // ❌ Wrong if screen name is 'Login'
```
