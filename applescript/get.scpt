tell application "Airfoil Speakers"
	activate
end tell
tell application "System Events"
	tell process "Airfoil Speakers"
		get value of attribute "AXValue" of slider 1 of UI element 1
	end tell
end tell
