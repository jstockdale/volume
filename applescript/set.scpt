on run argv
  tell application "Airfoil Speakers"
    activate
  end tell
  tell application "System Events"
    tell process "Airfoil Speakers"
      set value of attribute "AXValue" of slider 1 of UI element 1 to item 1 of argv as real
    end tell
  end tell
end run
